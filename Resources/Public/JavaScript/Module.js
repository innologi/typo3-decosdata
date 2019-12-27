/**
 * Module: TYPO3/CMS/Decosdata/Module
 */
define(['jquery'], function($) {
  'use strict';

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Decosdata/Module
   */
  var Module = {};

  /**
   * Registers listeners
   */
  Module.initializeEvents = function() {
    $('.t3js-update-button').on('click', function(event) {
      var $element = $(this);
      var name = $element.attr('name');
      var warning = $element.data('warning-message');
      var message = '';
      if (name === 'flushRoutingSlugs') {
        if (warning === undefined || confirm(warning)) {
          message = $element.data('notification-message');
          top.TYPO3.Notification.success(message);
        } else {
          event.preventDefault();
          return false;
        }
      }
    });
  };

  $(Module.initializeEvents);

  return Module;
});