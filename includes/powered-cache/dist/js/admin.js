!function(){var e={672:function(){const{__:e}=wp.i18n;var t;t=jQuery,"object"!==typeof window.PoweredCache&&(window.PoweredCache={}),PoweredCache.pageModals=function(){t("body").hasClass("toplevel_page_powered-cache")&&t("#pcmodal--powered-cache-diagnostic-test").on("click",(function(){const a=t(this).closest(".sui-box"),o=a.find(".sui-box-title"),i=a.find(".sui-box-header button"),s=a.find("#powered-cache-diagnostic-items"),d=t("#powered_cache_run_diagnostic").val(),n=e("Diagnostic","powered-cache");jQuery.ajax({url:ajaxurl,method:"post",beforeSend(){o.text(e("Running Diagnostic Tests…","powered-cache")),i.attr("disabled",!0),s.empty()},data:{nonce:d,action:"powered_cache_run_diagnostic"},success(e){e.success&&e.data.length&&e.data.forEach((e=>{let t=null;t=e.status?'<span class="sui-icon-check" aria-hidden="true"></span>':'<span class="sui-icon-close" aria-hidden="true"></span>',s.append(`<li>${t} ${e.description}</li>`)}))}}).done((function(){o.text(n),i.removeAttr("disabled")}))}))},t("body").ready((function(){PoweredCache.pageModals("modals")}))},242:function(){var e;e=jQuery,"object"!==typeof window.PoweredCache&&(window.PoweredCache={}),PoweredCache.sideNavigation=function(t){const a=e(t);return function(){a.on("click",(function(t){!function(t){const a=e(t),o=a.closest(".sui-vertical-tabs"),i=a.closest(".sui-row-with-sidenav"),s=i.find("> div[data-tab]"),d=a.data("tab"),n=i.find(`div[data-tab="${d}"]`);o.find("li").removeClass("current"),a.parent().addClass("current"),localStorage.setItem("poweredcache_current_nav",t.hash),s.hide(),n.show()}(t.target),t.preventDefault(),t.stopPropagation()})),window.location.hash&&e(`a[href="${window.location.hash}"]`).length&&e(`a[href^="${window.location.hash}"]`).click();const t=localStorage.getItem("poweredcache_current_nav");t&&e(`a[href="${t}"]`).length&&e(`a[href^="${t}"]`).click()}(),this},e("body").ready((function(){const t=e(".sui-vertical-tab a");PoweredCache.sideNavigation(t)}))},434:function(){var e;e=jQuery,"object"!==typeof window.PoweredCache&&(window.PoweredCache={}),PoweredCache.pageToggles=function(t){if(e("body").hasClass("toplevel_page_powered-cache"))return e('.sui-toggle input[type="checkbox"]').each((function(){const t=e(this);void 0!==t.attr("aria-controls")&&function(t){const a=e(`#${t.attr("aria-controls")}`);t.on("change",(function(){t.is(":checked")?a.show():a.hide()}))}(t)})),this},e("body").ready((function(){PoweredCache.pageToggles("toggles")}))},150:function(){function e(t){return e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},e(t)}!function(){"use strict";"object"!==e(window.SUI)&&(window.SUI={});var t=t||{};t.KeyCode={BACKSPACE:8,TAB:9,RETURN:13,ESC:27,SPACE:32,PAGE_UP:33,PAGE_DOWN:34,END:35,HOME:36,LEFT:37,UP:38,RIGHT:39,DOWN:40,DELETE:46},t.Utils=t.Utils||{},t.Utils.remove=function(e){return e.remove&&"function"===typeof e.remove?e.remove():!(!e.parentNode||!e.parentNode.removeChild||"function"!==typeof e.parentNode.removeChild)&&e.parentNode.removeChild(e)},t.Utils.isFocusable=function(e){if(0<e.tabIndex||0===e.tabIndex&&null!==e.getAttribute("tabIndex"))return!0;if(e.disabled)return!1;switch(e.nodeName){case"A":return!!e.href&&"ignore"!=e.rel;case"INPUT":return"hidden"!=e.type&&"file"!=e.type;case"BUTTON":case"SELECT":case"TEXTAREA":return!0;default:return!1}},t.Utils.simulateClick=function(e){var t=new MouseEvent("click",{bubbles:!0,cancelable:!0,view:window});e.dispatchEvent(t)},t.Utils.IgnoreUtilFocusChanges=!1,t.Utils.dialogOpenClass="sui-has-modal",t.Utils.focusFirstDescendant=function(e){for(var a=0;a<e.childNodes.length;a++){var o=e.childNodes[a];if(t.Utils.attemptFocus(o)||t.Utils.focusFirstDescendant(o))return!0}return!1},t.Utils.focusLastDescendant=function(e){for(var a=e.childNodes.length-1;0<=a;a--){var o=e.childNodes[a];if(t.Utils.attemptFocus(o)||t.Utils.focusLastDescendant(o))return!0}return!1},t.Utils.attemptFocus=function(e){if(!t.Utils.isFocusable(e))return!1;t.Utils.IgnoreUtilFocusChanges=!0;try{e.focus()}catch(e){}return t.Utils.IgnoreUtilFocusChanges=!1,document.activeElement===e},t.OpenDialogList=t.OpenDialogList||new Array(0),t.getCurrentDialog=function(){if(t.OpenDialogList&&t.OpenDialogList.length)return t.OpenDialogList[t.OpenDialogList.length-1]},t.closeCurrentDialog=function(){var e=t.getCurrentDialog();return!!e&&(e.close(),!0)},t.handleEscape=function(e){(e.which||e.keyCode)===t.KeyCode.ESC&&t.closeCurrentDialog()&&e.stopPropagation()},t.Dialog=function(a,o,i,s){var d=!(arguments.length>4&&void 0!==arguments[4])||arguments[4],n=!(arguments.length>5&&void 0!==arguments[5])||arguments[5];if(this.dialogNode=document.getElementById(a),null===this.dialogNode)throw new Error('No element found with id="'+a+'".');var r=["dialog","alertdialog"];if(!(this.dialogNode.getAttribute("role")||"").trim().split(/\s+/g).some((function(e){return r.some((function(t){return e===t}))})))throw new Error("Dialog() requires a DOM element with ARIA role of dialog or alertdialog.");this.isCloseOnEsc=d;var l=new Event("open");this.dialogNode.dispatchEvent(l);var c="sui-modal";if(this.dialogNode.parentNode.classList.contains(c)?this.backdropNode=this.dialogNode.parentNode:(this.backdropNode=document.createElement("div"),this.backdropNode.className=c,this.backdropNode.setAttribute("data-markup","new"),this.dialogNode.parentNode.insertBefore(this.backdropNode,this.dialogNodev),this.backdropNode.appendChild(this.dialogNode)),this.backdropNode.classList.add("sui-active"),document.body.parentNode.classList.add(t.Utils.dialogOpenClass),"string"===typeof o)this.focusAfterClosed=document.getElementById(o);else{if("object"!==e(o))throw new Error("the focusAfterClosed parameter is required for the aria.Dialog constructor.");this.focusAfterClosed=o}"string"===typeof i?this.focusFirst=document.getElementById(i):"object"===e(i)?this.focusFirst=i:this.focusFirst=null;var u=document.createElement("div");this.preNode=this.dialogNode.parentNode.insertBefore(u,this.dialogNode),this.preNode.tabIndex=0,"boolean"===typeof s&&!0===s&&(this.preNode.classList.add("sui-modal-overlay"),this.preNode.onclick=function(){t.getCurrentDialog().close()});var h=document.createElement("div");this.postNode=this.dialogNode.parentNode.insertBefore(h,this.dialogNode.nextSibling),this.postNode.tabIndex=0,0<t.OpenDialogList.length&&t.getCurrentDialog().removeListeners(),this.addListeners(),t.OpenDialogList.push(this),n?(this.dialogNode.classList.add("sui-content-fade-in"),this.dialogNode.classList.remove("sui-content-fade-out")):(this.dialogNode.classList.remove("sui-content-fade-in"),this.dialogNode.classList.remove("sui-content-fade-out")),this.focusFirst?this.focusFirst.focus():t.Utils.focusFirstDescendant(this.dialogNode),this.lastFocus=document.activeElement;var f=new Event("afterOpen");this.dialogNode.dispatchEvent(f)},t.Dialog.prototype.close=function(){var e=!(arguments.length>0&&void 0!==arguments[0])||arguments[0],a=this,o=new Event("close");this.dialogNode.dispatchEvent(o),t.OpenDialogList.pop(),this.removeListeners(),this.preNode.parentNode.removeChild(this.preNode),this.postNode.parentNode.removeChild(this.postNode),e?(this.dialogNode.classList.add("sui-content-fade-out"),this.dialogNode.classList.remove("sui-content-fade-in")):(this.dialogNode.classList.remove("sui-content-fade-in"),this.dialogNode.classList.remove("sui-content-fade-out")),this.focusAfterClosed.focus(),setTimeout((function(){a.backdropNode.classList.remove("sui-active")}),300),setTimeout((function(){var e=a.dialogNode.querySelectorAll(".sui-modal-slide");if(0<e.length){for(var t=0;t<e.length;t++)e[t].setAttribute("disabled",!0),e[t].classList.remove("sui-loaded"),e[t].classList.remove("sui-active"),e[t].setAttribute("tabindex","-1"),e[t].setAttribute("aria-hidden",!0);if(e[0].hasAttribute("data-modal-size")){var o=e[0].getAttribute("data-modal-size");switch(o){case"sm":case"small":o="sm";break;case"md":case"med":case"medium":o="md";break;case"lg":case"large":o="lg";break;case"xl":case"extralarge":case"extraLarge":case"extra-large":o="xl";break;default:o=void 0}void 0!==o&&(a.dialogNode.parentNode.classList.remove("sui-modal-sm"),a.dialogNode.parentNode.classList.remove("sui-modal-md"),a.dialogNode.parentNode.classList.remove("sui-modal-lg"),a.dialogNode.parentNode.classList.remove("sui-modal-xl"),a.dialogNode.parentNode.classList.add("sui-modal-"+o))}var i,s,d,n;if(e[0].classList.add("sui-active"),e[0].classList.add("sui-loaded"),e[0].removeAttribute("disabled"),e[0].removeAttribute("tabindex"),e[0].removeAttribute("aria-hidden"),e[0].hasAttribute("data-modal-labelledby"))i="",""===(s=e[0].getAttribute("data-modal-labelledby"))&&void 0===s||(i=s),a.dialogNode.setAttribute("aria-labelledby",i);if(e[0].hasAttribute("data-modal-describedby"))d="",""===(n=e[0].getAttribute("data-modal-describedby"))&&void 0===n||(d=n),a.dialogNode.setAttribute("aria-describedby",d)}}),350),0<t.OpenDialogList.length?t.getCurrentDialog().addListeners():document.body.parentNode.classList.remove(t.Utils.dialogOpenClass);var i=new Event("afterClose");this.dialogNode.dispatchEvent(i)},t.Dialog.prototype.replace=function(e,a,o,i){var s=!(arguments.length>4&&void 0!==arguments[4])||arguments[4],d=!(arguments.length>5&&void 0!==arguments[5])||arguments[5],n=this;t.OpenDialogList.pop(),this.removeListeners(),t.Utils.remove(this.preNode),t.Utils.remove(this.postNode),d?(this.dialogNode.classList.add("sui-content-fade-in"),this.dialogNode.classList.remove("sui-content-fade-out")):(this.dialogNode.classList.remove("sui-content-fade-in"),this.dialogNode.classList.remove("sui-content-fade-out")),this.backdropNode.classList.remove("sui-active"),setTimeout((function(){var e=n.dialogNode.querySelectorAll(".sui-modal-slide");if(0<e.length){for(var t=0;t<e.length;t++)e[t].setAttribute("disabled",!0),e[t].classList.remove("sui-loaded"),e[t].classList.remove("sui-active"),e[t].setAttribute("tabindex","-1"),e[t].setAttribute("aria-hidden",!0);if(e[0].hasAttribute("data-modal-size")){var a=e[0].getAttribute("data-modal-size");switch(a){case"sm":case"small":a="sm";break;case"md":case"med":case"medium":a="md";break;case"lg":case"large":a="lg";break;case"xl":case"extralarge":case"extraLarge":case"extra-large":a="xl";break;default:a=void 0}void 0!==a&&(n.dialogNode.parentNode.classList.remove("sui-modal-sm"),n.dialogNode.parentNode.classList.remove("sui-modal-md"),n.dialogNode.parentNode.classList.remove("sui-modal-lg"),n.dialogNode.parentNode.classList.remove("sui-modal-xl"),n.dialogNode.parentNode.classList.add("sui-modal-"+a))}var o,i,s,d;if(e[0].classList.add("sui-active"),e[0].classList.add("sui-loaded"),e[0].removeAttribute("disabled"),e[0].removeAttribute("tabindex"),e[0].removeAttribute("aria-hidden"),e[0].hasAttribute("data-modal-labelledby"))o="",""===(i=e[0].getAttribute("data-modal-labelledby"))&&void 0===i||(o=i),n.dialogNode.setAttribute("aria-labelledby",o);if(e[0].hasAttribute("data-modal-describedby"))s="",""===(d=e[0].getAttribute("data-modal-describedby"))&&void 0===d||(s=d),n.dialogNode.setAttribute("aria-describedby",s)}}),350);var r=a||this.focusAfterClosed;new t.Dialog(e,r,o,i,s,d)},t.Dialog.prototype.slide=function(a,o,i){var s,d,n,r,l="sui-fadein",c=(t.getCurrentDialog(),this.dialogNode.querySelectorAll(".sui-modal-slide")),u=document.getElementById(a);switch(i){case"back":case"left":l="sui-fadein-left";break;case"next":case"right":l="sui-fadein-right";break;default:l="sui-fadein"}for(var h=0;h<c.length;h++)c[h].setAttribute("disabled",!0),c[h].classList.remove("sui-loaded"),c[h].classList.remove("sui-active"),c[h].setAttribute("tabindex","-1"),c[h].setAttribute("aria-hidden",!0);if(u.hasAttribute("data-modal-size")){var f=u.getAttribute("data-modal-size");switch(f){case"sm":case"small":f="sm";break;case"md":case"med":case"medium":f="md";break;case"lg":case"large":f="lg";break;case"xl":case"extralarge":case"extraLarge":case"extra-large":f="xl";break;default:f=void 0}void 0!==f&&(this.dialogNode.parentNode.classList.remove("sui-modal-sm"),this.dialogNode.parentNode.classList.remove("sui-modal-md"),this.dialogNode.parentNode.classList.remove("sui-modal-lg"),this.dialogNode.parentNode.classList.remove("sui-modal-xl"),this.dialogNode.parentNode.classList.add("sui-modal-"+f))}u.hasAttribute("data-modal-labelledby")&&(s="",""===(d=u.getAttribute("data-modal-labelledby"))&&void 0===d||(s=d),this.dialogNode.setAttribute("aria-labelledby",s));u.hasAttribute("data-modal-describedby")&&(n="",""===(r=u.getAttribute("data-modal-describedby"))&&void 0===r||(n=r),this.dialogNode.setAttribute("aria-describedby",n));u.classList.add("sui-active"),u.classList.add(l),u.removeAttribute("tabindex"),u.removeAttribute("aria-hidden"),setTimeout((function(){u.classList.add("sui-loaded"),u.classList.remove(l),u.removeAttribute("disabled")}),600),"string"===typeof o?this.newSlideFocus=document.getElementById(o):"object"===e(o)?this.newSlideFocus=o:this.newSlideFocus=null,this.newSlideFocus?this.newSlideFocus.focus():t.Utils.focusFirstDescendant(this.dialogNode)},t.Dialog.prototype.addListeners=function(){document.addEventListener("focus",this.trapFocus,!0),this.isCloseOnEsc&&this.dialogNode.addEventListener("keyup",t.handleEscape)},t.Dialog.prototype.removeListeners=function(){document.removeEventListener("focus",this.trapFocus,!0)},t.Dialog.prototype.trapFocus=function(e){if(!t.Utils.IgnoreUtilFocusChanges){var a=t.getCurrentDialog();a.dialogNode.contains(e.target)?a.lastFocus=e.target:(t.Utils.focusFirstDescendant(a.dialogNode),a.lastFocus==document.activeElement&&t.Utils.focusLastDescendant(a.dialogNode),a.lastFocus=document.activeElement)}},SUI.openModal=function(e,a,o,i){var s=!(arguments.length>4&&void 0!==arguments[4])||arguments[4],d=arguments.length>5?arguments[5]:void 0;new t.Dialog(e,a,o,i,s,d)},SUI.closeModal=function(e){t.getCurrentDialog().close(e)},SUI.replaceModal=function(e,a,o,i){var s=!(arguments.length>4&&void 0!==arguments[4])||arguments[4],d=arguments.length>5?arguments[5]:void 0;t.getCurrentDialog().replace(e,a,o,i,s,d)},SUI.slideModal=function(e,a,o){t.getCurrentDialog().slide(e,a,o)}}(),function(t){"use strict";"object"!==e(window.SUI)&&(window.SUI={}),SUI.modalDialog=function(){return function(){var a,o,i,s,d,n,r,l,c,u,h,f;o=t("[data-modal-open]"),i=t("[data-modal-close]"),s=t("[data-modal-replace]"),d=t("[data-modal-slide]"),n=t(".sui-modal-overlay"),o.on("click",(function(o){a=t(this),r=a.attr("data-modal-open"),c=a.attr("data-modal-close-focus"),u=a.attr("data-modal-open-focus"),n=a.attr("data-modal-mask"),f=a.attr("data-modal-animated");var i="false"!==a.attr("data-esc-close");"undefined"!==e(c)&&!1!==c&&""!==c||(c=this),"undefined"!==e(u)&&!1!==u&&""!==u||(u=void 0),n="undefined"!==e(n)&&!1!==n&&"true"===n,f="undefined"===e(f)||!1===f||"false"!==f,"undefined"!==e(r)&&!1!==r&&""!==r&&SUI.openModal(r,c,u,n,i,f),o.preventDefault()})),s.on("click",(function(o){a=t(this),r=a.attr("data-modal-replace"),c=a.attr("data-modal-close-focus"),u=a.attr("data-modal-open-focus"),n=a.attr("data-modal-replace-mask");var i="false"!==a.attr("data-esc-close");"undefined"!==e(c)&&!1!==c&&""!==c||(c=void 0),"undefined"!==e(u)&&!1!==u&&""!==u||(u=void 0),n="undefined"!==e(n)&&!1!==n&&"true"===n,"undefined"!==e(r)&&!1!==r&&""!==r&&SUI.replaceModal(r,c,u,n,i,f),o.preventDefault()})),d.on("click",(function(o){a=t(this),l=a.attr("data-modal-slide"),u=a.attr("data-modal-slide-focus"),h=a.attr("data-modal-slide-intro"),"undefined"!==e(u)&&!1!==u&&""!==u||(u=void 0),"undefined"!==e(h)&&!1!==h&&""!==h||(h=""),"undefined"!==e(l)&&!1!==l&&""!==l&&SUI.slideModal(l,u,h),o.preventDefault()})),i.on("click",(function(e){SUI.closeModal(f),e.preventDefault()}))}(),this},SUI.modalDialog()}(jQuery)}},t={};function a(o){var i=t[o];if(void 0!==i)return i.exports;var s=t[o]={exports:{}};return e[o](s,s.exports,a),s.exports}a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,{a:t}),t},a.d=function(e,t){for(var o in t)a.o(t,o)&&!a.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){"use strict";a(150),a(242),a(434),a(672);!function(e){let t=0;e(".add_cdn_hostname").click((function(a){a.preventDefault();let o=e(".cdn-zone:first").clone();0===t?t=e(".cdn-zone").length:t++,o=e(o).attr("id",`cdn-zone-${t}`),e(o).find(".cdn_hostname").val(""),e(o).find(".cdn_zone").prop("selectedIndex",0),e(o).find("button").removeClass("sui-hidden-important"),e("#cdn-zones").append(o)})),e("#cdn-zones").on("click",".remove_cdn_hostname",(function(){const t=e(this).parents(".cdn-zone");return"cdn-zone-0"===t.attr("id")?(alert("Nice try :) This zone cannot be removed!"),!1):(t.remove(),!0)})),e("#enable_cache_preload").on("change",(function(){e(this).is(":checked")?e("#enable_sitemap_preload_wrapper").show():e("#enable_sitemap_preload_wrapper").hide()})),e("#enable_page_cache").on("change",(function(){e(this).is(":checked")?e("#preload_page_cache_warning_message").hide():e("#preload_page_cache_warning_message").show()})),e("#cloudflare-api-token").on("keyup keypress change",(function(){e(this).val().length>0?e("#cloudflare-api-details").hide():e("#cloudflare-api-details").show()})),e(".heartbeat_dashboard_status").on("change",(function(){"modify"===e(this).val()?e("#heartbeat-dashboard-interval").show():e("#heartbeat-dashboard-interval").hide()})),e(".heartbeat_radio").on("change",(function(){const t=e(this).attr("aria-controls");t&&("modify"===e(this).val()?e(`#${t}`).show():e(`#${t}`).hide())})),e("#powered-cache-import-file-input").on("change",(function(){const t=e(this)[0];if(t.files.length){const a=t.files[0];e("#powered-cache-import-file-name").text(a.name),e("#powered-cache-import-upload-wrap").addClass("sui-has_file"),e("#powered-cache-import-btn").removeAttr("disabled")}else e("#powered-cache-import-file-name").text(""),e("#powered-cache-import-upload-wrap").removeClass("sui-has_file"),e("#powered-cache-import-btn").attr("disabled","disabled")})),e("#powered-cache-import-remove-file").on("click",(function(){e("#powered-cache-import-file-input").val("").trigger("change")}))}(jQuery)}()}();