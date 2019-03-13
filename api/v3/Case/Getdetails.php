<?php
require_once 'api/v3/Case.php';

/**
 * Case.getdetails API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_case_getdetails_spec(&$spec) {
  $result = civicrm_api3('Case', 'getfields', array('api_action' => 'get'));
  $spec = $result['values'];

  $spec['case_manager'] = array(
    'title' => 'Case Manager',
    'description' => 'Contact id of the case manager',
    'type' => CRM_Utils_Type::T_INT,
  );

  $spec['contact_involved'] = array(
    'title' => 'Contact Involved',
    'description' => 'Id of the contact involved as case roles',
    'type' => CRM_Utils_Type::T_INT,
  );

  $spec['has_role'] = [
    'title' => 'Case has role',
    'description' => '{ contact, role_type, can_be_client }',
    'type' => CRM_Utils_Type::T_STRING,
  ];

  $spec['contact_is_deleted'] = array(
    'title' => 'Contact Is Deleted',
    'description' => 'Set FALSE to filter out cases for deleted contacts, TRUE to return only cases of deleted contacts',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
}

/**
 * Adds contacts involved param clause
 *
 * @param array $params
 * @param object $sql
 * @throws API_Exception
 * @return void
 */
function _civicrm_api3_case_add_contact_involved_clause($params, &$sql) {
  $caseClient = CRM_Core_DAO::createSQLFilter('contact_id', $params['contact_involved']);
  $nonCaseClient = CRM_Core_DAO::createSQLFilter('involved.id', $params['contact_involved']);
  $sql->where("a.id IN (SELECT case_id FROM civicrm_case_contact WHERE ($nonCaseClient OR $caseClient))");
}

/**
 * Case.getdetails API
 * This is provided by the CiviCase extension. It gives more robust output than the regular get action.
 *
 * @param array $params
 * @return array API result
 * @throws API_Exception
 */
function civicrm_api3_case_getdetails($params) {
  $resultMetadata = array();
  $params += array('return' => array());
  if (is_string($params['return'])) {
    $params['return'] = explode(',', str_replace(' ', '', $params['return']));
  }
  $toReturn = $params['return'];
  $params['options'] = CRM_Utils_Array::value('options', $params, array());
  $extraReturnProperties = array('activity_summary', 'last_update', 'activity_count', 'category_count', 'unread_email_count', 'related_case_ids');
  $params['return'] = array_diff($params['return'], $extraReturnProperties);

  // Support additional sort params
  $sql = _civicrm_api3_case_getdetails_extrasort($params);

  // Add clause to search by manager
  if (!empty($params['case_manager'])) {
    if (!is_array($params['case_manager'])) {
      $params['case_manager'] = array('=' => $params['case_manager']);
    }
    \Civi\CCase\Utils::joinOnRelationship($sql, 'manager');
    $sql->where(CRM_Core_DAO::createSQLFilter('manager.id', $params['case_manager']));
  }

  if (!empty($params['has_role'])) {
    _civicrm_api3_case_getdetails_handle_role_filters($params, $sql);
  }

  // Add clause to search by non manager role and non client
  if (!empty($params['contact_involved'])) {
    if (!is_array($params['contact_involved'])) {
      $params['contact_involved'] = array('=' => $params['contact_involved']);
    }
    \Civi\CCase\Utils::joinOnRelationship($sql, 'involved');
    _civicrm_api3_case_add_contact_involved_clause($params, $sql);
  }

  // Filter deleted contacts from results
  if (isset($params['contact_is_deleted'])) {
    $isDeleted = (int) $params['contact_is_deleted'];
    $sql->where("a.id IN (SELECT case_id FROM civicrm_case_contact ccc, civicrm_contact cc WHERE ccc.contact_id = cc.id AND cc.is_deleted = $isDeleted)");
  }

  // Set page number dynamically based on selected record
  if (!empty($params['options']['page_of_record'])) {
    $prParams = array('sequential' => 1) + $params;
    $prParams['return'] = array('id');
    $prParams['options']['limit'] = $prParams['options']['offset'] = 0;
    foreach (CRM_Utils_Array::value('values', civicrm_api3_case_get($prParams), array()) as $num => $case) {
      if ($case['id'] == $params['options']['page_of_record']) {
        $resultMetadata['page'] = floor($num / $params['options']['limit']) + 1;
        $params['options']['offset'] = $params['options']['limit'] * ($resultMetadata['page'] - 1);
        break;
      }
    }
  }

  // Call the case api
  $result = civicrm_api3_case_get(array('sequential' => 0) + $params, $sql);
  if (!empty($result['values'])) {
    $ids = array_keys($result['values']);

    // Remove legacy cruft
    foreach ($result['values'] as &$case) {
      unset($case['client_id']);
    }

    $activityCategories = civicrm_api3('OptionValue', 'get', array(
      'return' => array('name'),
      'option_group_id' => "activity_category",
    ));
    $activityCategories = CRM_Utils_Array::collect('name', $activityCategories['values']);

    // Get activity summary
    if (in_array('activity_summary', $toReturn)) {
      $catetoryLimits = CRM_Utils_Array::value('categories', $params['options'], array_fill_keys($activityCategories, 1));
      $categories = array_fill_keys(array_keys($catetoryLimits), array());
      foreach ($result['values'] as &$case) {
        $case['activity_summary'] = $categories;
      }
      $allTypes = array();
      foreach (array_keys($categories) as $grouping) {
        $option = civicrm_api3('OptionValue', 'get', array(
          'return' => array('value'),
          'option_group_id' => 'activity_type',
          'grouping' => array('LIKE' => "%{$grouping}%"),
          'options' => array('limit' => 0),
        ));
        foreach ($option['values'] as $val) {
          $categories[$grouping][] = $allTypes[] = $val['value'];
        }
      }
      $activities = civicrm_api3('Activity', 'get', array(
        'return' => array('activity_type_id', 'subject', 'activity_date_time', 'status_id', 'case_id', 'target_contact_name', 'assignee_contact_name', 'is_overdue', 'is_star', 'file_id', 'tag_id.name', 'tag_id.description', 'tag_id.color'),
        'check_permissions' => !empty($params['check_permissions']),
        'case_id' => array('IN' => $ids),
        'is_current_revision' => 1,
        'is_test' => 0,
        'status_id.filter' => CRM_Activity_BAO_Activity::INCOMPLETE,
        'options' => array(
          'limit' => 0,
          'sort' => 'activity_date_time',
        ),
      ));
      foreach ($activities['values'] as $act) {
        foreach ((array) $act['case_id'] as $actCaseId) {
          if (isset($result['values'][$actCaseId])) {
            $case =& $result['values'][$actCaseId];
            if (!isset($case['activity_summary']['next'])) {
              $case['activity_summary']['next'][] = $act;
            }
            foreach ($categories as $category => $grouping) {
              if (in_array($act['activity_type_id'], $grouping) && (empty($catetoryLimits[$category]) || count($case['activity_summary'][$category]) < $catetoryLimits[$category])) {
                $case['activity_summary'][$category][] = $act;
              }
            }
          }
        }
      }
    }
    // Get activity count
    if (in_array('activity_count', $toReturn)) {
      foreach ($result['values'] as $id => &$case) {
        $query = "SELECT COUNT(a.id) as count, a.activity_type_id
          FROM civicrm_activity a
          INNER JOIN civicrm_case_activity ca ON ca.activity_id = a.id
          WHERE a.is_current_revision = 1 AND a.is_test = 0 AND ca.case_id = $id
          GROUP BY a.activity_type_id";
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $case['activity_count'][$dao->activity_type_id] = $dao->count;
        }
      }
    }
    // Get count of activities by category
    if (in_array('category_count', $toReturn)) {
      $statusTypes = array(
        'incomplete' => implode(',', array_keys(\CRM_Activity_BAO_Activity::getStatusesByType(\CRM_Activity_BAO_Activity::INCOMPLETE))),
        'completed' => implode(',', array_keys(\CRM_Activity_BAO_Activity::getStatusesByType(\CRM_Activity_BAO_Activity::COMPLETED))),
      );
      // Creates category_count object with empty values
      foreach ($result['values'] as &$case) {
        $case['category_count'] = array_fill_keys(array_values($activityCategories), array());
      }

      // fills each category with respective counts
      foreach ($activityCategories as $category) {
        // calculates complete and incomplete activities
        foreach ($statusTypes as $statusType => $statusTypeIds) {
          calculate_activities_for_category($category, $ids, $statusTypeIds, $statusType, FALSE, $result);
        }
        // calculates overdue activities
        calculate_activities_for_category($category, $ids, $statusTypes['incomplete'], $statusType, TRUE, $result);
      }

      // calculates activities which does not have any activity category
      foreach ($statusTypes as $statusType => $statusTypeIds) {
        calculate_activities_for_category(NULL, $ids, $statusTypeIds, $statusType, FALSE, $result);
      }

      // calculates overdue activities which does not have any activity category
      calculate_activities_for_category(NULL, $ids, $statusTypes['incomplete'], $statusType, TRUE, $result);
    }
    // Unread email activity count
    if (in_array('unread_email_count', $toReturn)) {
      $query = "SELECT COUNT(a.id) as count, ca.case_id
        FROM civicrm_activity a, civicrm_case_activity ca
        WHERE ca.activity_id = a.id AND a.is_current_revision = 1 AND a.is_test = 0 AND ca.case_id IN (" . implode(',', $ids) . ")
        AND a.activity_type_id = (SELECT value FROM civicrm_option_value WHERE name = 'Inbound Email' AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_type'))
        AND a.status_id = (SELECT value FROM civicrm_option_value WHERE name = 'Unread' AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_status'))
        GROUP BY ca.case_id";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $result['values'][$dao->case_id]['unread_email_count'] = (int) $dao->count;
      }
    }
    // Get related_case_ids
    if (in_array('related_case_ids', $toReturn)) {
      foreach ($result['values'] as &$case) {
        $case['related_case_ids'] = CRM_Case_BAO_Case::getRelatedCaseIds($case['id']);
      }
    }
    // Get last update
    if (in_array('last_update', $toReturn)) {
      // todo
    }
    if (!empty($params['sequential'])) {
      $result['values'] = array_values($result['values']);
    }
  }
  return $resultMetadata + $result;
}

/**
 * Calculates the number of activities for the given category
 *
 * @param {String} $category
 * @param {Array} $ids
 * @param {String} $statusTypeIds
 * @param {String} $statusType
 * @param {Boolean} $isOverdue
 * @param {Array} $result
 */
function calculate_activities_for_category($category, $ids, $statusTypeIds, $statusType, $isOverdue, &$result) {
  $isOverdueCondition = $isOverdue ? "AND a.activity_date_time < NOW()" : "";
  $categoryCondition = empty($category) ? "IS NULL" : "LIKE '%$category%'";

  $query = "SELECT COUNT(a.id) as count, ca.case_id
  FROM civicrm_activity a, civicrm_case_activity ca
  WHERE ca.activity_id = a.id AND a.is_current_revision = 1 AND a.is_test = 0 AND ca.case_id IN (" . implode(',', $ids) . ")
  AND a.activity_type_id IN (SELECT value FROM civicrm_option_value WHERE grouping "
  . $categoryCondition ." AND option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'activity_type'))
  ". $isOverdueCondition ."
  AND is_current_revision = 1
  AND is_deleted = 0
  AND a.status_id IN ($statusTypeIds)
  GROUP BY ca.case_id";

  $dao = CRM_Core_DAO::executeQuery($query);

  while ($dao->fetch()) {
    $categoryName = empty($category) ? 'none' : $category;
    $statusTypeName = $isOverdue ? "overdue" : $statusType;
    $result['values'][$dao->case_id]['category_count'][$categoryName][$statusTypeName] = (int) $dao->count;
  }
}
/**
 * Support extra sorting in case.getdetails.
 *
 * @param $params
 * @return \CRM_Utils_SQL_Select
 * @throws \API_Exception
 */
function _civicrm_api3_case_getdetails_extrasort(&$params) {
  $sql = CRM_Utils_SQL_Select::fragment();
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['sort'])) {
    $sort = explode(', ', $options['sort']);

    // For each one of our special fields we swap it for the placeholder (1) so it will be ignored by the case api.
    foreach ($sort as $index => &$sortString) {
      // Get sort field and direction
      list($sortField, $dir) = array_pad(explode(' ', $sortString), 2, 'ASC');
      list($sortJoin, $sortField) = array_pad(explode('.', $sortField), 2, 'id');
      // Sort by case manager
      if ($sortJoin == 'case_manager') {
        // Validate inputs
        if (!array_key_exists($sortField, CRM_Contact_DAO_Contact::fieldKeys()) || ($dir != 'ASC' && $dir != 'DESC')) {
          throw new API_Exception("Unknown field specified for sort. Cannot order by '$sortString'");
        }
        \Civi\CCase\Utils::joinOnRelationship($sql, 'manager');
        $sql->orderBy("manager.$sortField $dir", NULL, $index);
        $sortString = '(1)';
      }
      // Sort by my role
      elseif ($sortJoin == 'my_role') {
        $me = CRM_Core_Session::getLoggedInContactID();
        // Validate inputs
        if (!array_key_exists($sortField, CRM_Contact_DAO_RelationshipType::fieldKeys()) || ($dir != 'ASC' && $dir != 'DESC')) {
          throw new API_Exception("Unknown field specified for sort. Cannot order by '$sortString'");
        }
        $sql->join('ccc', 'LEFT JOIN (SELECT * FROM civicrm_case_contact WHERE id IN (SELECT MIN(id) FROM civicrm_case_contact GROUP BY case_id)) AS ccc ON ccc.case_id = a.id');
        $sql->join('my_relationship', "LEFT JOIN civicrm_relationship AS my_relationship ON ccc.contact_id = my_relationship.contact_id_a AND my_relationship.is_active AND my_relationship.contact_id_b = $me AND my_relationship.case_id = a.id");
        $sql->join('my_relationship_type', 'LEFT JOIN civicrm_relationship_type AS my_relationship_type ON my_relationship_type.id = my_relationship.relationship_type_id');
        $sql->orderBy("my_relationship_type.$sortField $dir", NULL, $index);
        $sortString = '(1)';
      }
      // Sort by upcoming activities
      elseif (strpos($sortString, 'next_activity') === 0) {
        $sortString = '(1)';
        $category = str_replace('next_activity_category_', '', $sortJoin);
        $actClause = '';
        // If we're limiting to a particiular category
        if ($category != 'next_activity') {
          $actTypes = civicrm_api3('OptionValue', 'get', array(
            'sequential' => 1,
            'option_group_id' => "activity_type",
            'options' => array('limit' => 0),
            'grouping' => array('LIKE' => "%$category%"),
          ));
          $actTypes = implode(',', CRM_Utils_Array::collect('value', $actTypes['values']));
          if (!$actTypes) {
            continue;
          }
          $actClause = "AND activity_type_id IN ($actTypes)";
        }
        $incomplete = implode(',', array_keys(\CRM_Activity_BAO_Activity::getStatusesByType(\CRM_Activity_BAO_Activity::INCOMPLETE)));
        $sql->join($sortJoin, "LEFT JOIN (
            SELECT MIN(activity_date_time) as activity_date_time, case_id
            FROM civicrm_activity, civicrm_case_activity
            WHERE civicrm_activity.id = civicrm_case_activity.activity_id $actClause AND status_id IN ($incomplete) AND is_current_revision = 1 AND is_test <> 1
            GROUP BY case_id
          ) AS $sortJoin ON $sortJoin.case_id = a.id");
        $sql->orderBy("$sortJoin.activity_date_time $dir", NULL, $index);
      }
    }
    // Remove our extra sort params so the basic_get function doesn't see them
    $params['options']['sort'] = implode(', ', $sort);
    unset($params['option_sort'], $params['option.sort'], $params['sort']);
  }

  return $sql;
}

/**
 * Filters cases by contacts related to the case and their relationship types.
 *
 * @param array $params as provided by the original api action.
 * @param object $sql a reference to the SQL object.
 */
function _civicrm_api3_case_getdetails_handle_role_filters ($params, $sql) {
  $hasRole = $params['has_role'];
  $canBeAClient = !isset($hasRole['can_be_client']) || $hasRole['can_be_client'];

  _civicase_prepare_param_for_filtering($hasRole, 'contact');

  $roleContactFilter = CRM_Core_DAO::createSQLFilter('case_relationship.contact_id_b', $hasRole['contact']);
  $clientFilter = CRM_Core_DAO::createSQLFilter('case_client.contact_id', $hasRole['contact']);

  $sql->join('case_relationship', 'LEFT JOIN civicrm_relationship AS case_relationship
    ON case_relationship.case_id = a.id');
  $sql->where('case_relationship.is_active = 1');

  if (!empty($hasRole['role_type'])) {
    _civicase_prepare_param_for_filtering($hasRole, 'role_type');

    $roleTypeFilter = CRM_Core_DAO::createSQLFilter('case_relationship.relationship_type_id', $hasRole['role_type']);
    $roleContactFilter = "($roleContactFilter AND $roleTypeFilter)";
  }

  if ($canBeAClient) {
    $sql->join('case_client', 'LEFT JOIN civicrm_case_contact AS case_client
      ON case_client.case_id = a.id');
    $sql->where("$roleContactFilter OR $clientFilter");
  } else {
    $sql->where($roleContactFilter);
  }
}

/**
 * Corrects the param structure if not organized using the array notation.
 *   From ['paramName' => 'value']
 *   To ['paramName' => ['=' => 'value']]
 * The later is the expected format when using `CRM_Core_DAO::createSQLFilter`.
 *
 * @param array $params the list of params as provided by the action.
 * @param string $paramName the name of the specific parameter to fix.
 */
function _civicase_prepare_param_for_filtering (&$params, $paramName) {
  if (!is_array($params[$paramName])) {
    $params[$paramName] = [
      '=' => $params[$paramName]
    ];
  }
}
