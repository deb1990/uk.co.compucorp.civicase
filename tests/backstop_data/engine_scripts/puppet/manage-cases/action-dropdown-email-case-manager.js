'use strict';

module.exports = async (page, scenario, vp) => {
  await require('./action-dropdown')(page, scenario, vp);
  await page.click('.civicase__case-header__action-menu .btn-group:last-child .dropdown-menu li .fa-envelope-o');
  await page.waitFor('.civicase__email-role-selector');
};
