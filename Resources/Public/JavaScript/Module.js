/**
 * Module: TYPO3/CMS/Decosdata/Module
 */
import Notification from"@typo3/backend/notification.js";
import RegularEvent from"@typo3/core/event/regular-event.js";
var Selectors;

!function(t){t.actionButtonSelector=".t3js-update-button"}(Selectors||(Selectors={}));

class Decosdata{
    constructor(){this.initializeEvents()}
    initializeEvents(){
        new RegularEvent("click", (t, e) => {
            Notification.success(e.dataset.notificationMessage||"Event triggered", "", 3)
        }).delegateTo(document, Selectors.actionButtonSelector)
    }
}
export default new Decosdata;