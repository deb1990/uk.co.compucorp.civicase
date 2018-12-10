<?php

class CRM_Civicase_Activity_ContactActivitiesSelector {

  const API_DEFAULT_LIMIT = 25;

  /**
   * Get the activities for a given contact. The contact must be either the creator,
   * the client, or be one of the assignees for the activity. Also, the activity should
   * not be assigned to someone else unless the contact is also an assignee.
   *
   * @param array
   * @return array
   */
  public function getActivitiesForContact($params) {
    $newParams = $this->getParamsWithoutOffsetsAndLimits($params);

    $this->addAssigneeContactIdToReturnParams($newParams);

    $activities = civicrm_api3('Activity', 'get', $newParams);

    if ($activities['error']) {
      return $activities;
    }

    $this->filterOutActivitiesNotBelongingToContact($activities, $newParams);
    $this->paginateActivityRecords($activities, $params);

    return $activities;
  }

  /**
   * Returns the original parameters, but without any offsets or limits.
   *
   * @param array $params
   * @return array
   */
  private function getParamsWithoutOffsetsAndLimits($params) {
    $options = CRM_Utils_Array::value('options', $params, []);
    $options['limit'] = 0;
    $options['offset'] = 0;
    $params['options'] = $options;

    return $params;
  }

  /**
   * Adds the `assignee_contact_id` field to the return parameter. This field
   * is necesary in order to properly filter the activities for the contact and
   * remove activities that have been delegated to someone else.
   *
   * @param array $params
   */
  private function addAssigneeContactIdToReturnParams(&$params) {
    $return = (array) CRM_Utils_Array::value('return', $params, []);
    $return[] = 'assignee_contact_id';
    $return = array_unique($return);
    $return = implode(',', $return);
    $params['return'] = $return;
  }

  /**
   * Removes any activities that have been assigned to another contact other than
   * the requested one. It also updates the activities count in order to reflect the
   * new value.
   *
   * @param array $activites
   * @param array $params
   * @return array
   */
  private function filterOutActivitiesNotBelongingToContact(&$activities, $params) {
    $activities['values'] = array_filter($activities['values'], function ($activity) use ($params) {
      $hasNoAssignee = !isset($activity['assignee_contact_id']) || empty($activity['assignee_contact_id']);
      $isContactAssignedToActivity = in_array($params['contact_id'], $activity['assignee_contact_id']);

      return $hasNoAssignee || $isContactAssignedToActivity;
    });

    $activities['count'] = count($activities['values']);
  }

  /**
   * Paginates the activity records according to the limit and offset params.
   *
   * @param array $activites
   * @param array $params
   * @return array
   */
  private function paginateActivityRecords(&$activities, $params) {
    $options = CRM_Utils_Array::value('options', $params, []);
    $limit = CRM_Utils_Array::value('limit', $options, self::API_DEFAULT_LIMIT);
    $offset = CRM_Utils_Array::value('offset', $options, 0);

    $activities['values'] = array_slice($activities['values'], $offset, $limit);
  }
}
