/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/editor-styles.css":
/*!*******************************!*\
  !*** ./src/editor-styles.css ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!*******************************!*\
  !*** external ["wp","blocks"] ***!
  \*******************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!***********************************!*\
  !*** external ["wp","components"] ***!
  \***********************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/compose":
/*!********************************!*\
  !*** external ["wp","compose"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["compose"];

/***/ }),

/***/ "@wordpress/data":
/*!*****************************!*\
  !*** external ["wp","data"] ***!
  \*****************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/date":
/*!*****************************!*\
  !*** external ["wp","date"] ***!
  \*****************************/
/***/ ((module) => {

module.exports = window["wp"]["date"];

/***/ }),

/***/ "@wordpress/element":
/*!********************************!*\
  !*** external ["wp","element"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/hooks":
/*!******************************!*\
  !*** external ["wp","hooks"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["hooks"];

/***/ }),

/***/ "@wordpress/i18n":
/*!*****************************!*\
  !*** external ["wp","i18n"] ***!
  \*****************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/date */ "@wordpress/date");
/* harmony import */ var _wordpress_date__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_date__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _editor_styles_css__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./editor-styles.css */ "./src/editor-styles.css");









const { sprintf } = _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__;
const DEVICE_VISIBILITY_OPTIONS = [{
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Visible sur tous les appareils', 'visi-bloc-jlg'),
  value: 'all'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('--- Afficher uniquement sur ---', 'visi-bloc-jlg'),
  value: 'separator-show',
  disabled: true
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Desktop Uniquement', 'visi-bloc-jlg'),
  value: 'desktop-only'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Tablette Uniquement', 'visi-bloc-jlg'),
  value: 'tablet-only'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Mobile Uniquement', 'visi-bloc-jlg'),
  value: 'mobile-only'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('--- Cacher sur ---', 'visi-bloc-jlg'),
  value: 'separator-hide',
  disabled: true
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Caché sur Desktop', 'visi-bloc-jlg'),
  value: 'hide-on-desktop'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Caché sur Tablette', 'visi-bloc-jlg'),
  value: 'hide-on-tablet'
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Caché sur Mobile', 'visi-bloc-jlg'),
  value: 'hide-on-mobile'
}];
function addVisibilityAttributesToGroup(settings, name) {
  if (name !== 'core/group') {
    return settings;
  }
  settings.attributes = {
    ...settings.attributes,
    isHidden: {
      type: 'boolean',
      default: false
    },
    deviceVisibility: {
      type: 'string',
      default: 'all'
    },
    isSchedulingEnabled: {
      type: 'boolean',
      default: false
    },
    publishStartDate: {
      type: 'string'
    },
    publishEndDate: {
      type: 'string'
    },
    visibilityRoles: {
      type: 'array',
      default: []
    }
  };
  return settings;
}
const withVisibilityControls = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__.createHigherOrderComponent)(BlockEdit => {
  return props => {
    if (props.name !== 'core/group') {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(BlockEdit, props);
    }
    const {
      attributes,
      setAttributes,
      isSelected
    } = props;
    const {
      isHidden,
      deviceVisibility,
      isSchedulingEnabled,
      publishStartDate,
      publishEndDate,
      visibilityRoles
    } = attributes;
    const onRoleChange = (isChecked, roleSlug) => {
      const newRoles = isChecked ? [...visibilityRoles, roleSlug] : visibilityRoles.filter(role => role !== roleSlug);
      setAttributes({
        visibilityRoles: newRoles
      });
    };
    let scheduleSummary = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Aucune programmation.', 'visi-bloc-jlg');
    if (isSchedulingEnabled) {
      const startDate = publishStartDate ? (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_6__.format)('d/m/Y H:i', publishStartDate) : null;
      const endDate = publishEndDate ? (0,_wordpress_date__WEBPACK_IMPORTED_MODULE_6__.format)('d/m/Y H:i', publishEndDate) : null;
      if (startDate && endDate) {
        scheduleSummary = sprintf((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Du %s au %s.', 'visi-bloc-jlg'), startDate, endDate);
      } else if (startDate) {
        scheduleSummary = sprintf((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('À partir du %s.', 'visi-bloc-jlg'), startDate);
      } else if (endDate) {
        scheduleSummary = sprintf((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Jusqu\'au %s.', 'visi-bloc-jlg'), endDate);
      } else {
        scheduleSummary = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Activée, mais sans date définie.', 'visi-bloc-jlg');
      }
    }
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(BlockEdit, props), isSelected && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.BlockControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.ToolbarGroup, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.ToolbarButton, {
      icon: "visibility",
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Rendre visible', 'visi-bloc-jlg'),
      onClick: () => setAttributes({
        isHidden: false
      }),
      isActive: isHidden === false
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.ToolbarButton, {
      icon: "hidden",
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Rendre caché', 'visi-bloc-jlg'),
      onClick: () => setAttributes({
        isHidden: true
      }),
      isActive: isHidden === true
    }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Contrôles de Visibilité', 'visi-bloc-jlg'),
      initialOpen: true
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Visibilité par Appareil', 'visi-bloc-jlg'),
      value: deviceVisibility,
      options: DEVICE_VISIBILITY_OPTIONS,
      onChange: newValue => setAttributes({
        deviceVisibility: newValue
      })
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Programmation', 'visi-bloc-jlg'),
      initialOpen: false,
      className: "visi-bloc-panel-schedule"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.ToggleControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Activer la programmation', 'visi-bloc-jlg'),
      checked: isSchedulingEnabled,
      onChange: () => setAttributes({
        isSchedulingEnabled: !isSchedulingEnabled
      })
    }), isSchedulingEnabled && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      style: {
        fontStyle: 'italic',
        color: '#555'
      }
    }, scheduleSummary), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CheckboxControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Définir une date de début', 'visi-bloc-jlg'),
      checked: !!publishStartDate,
      onChange: isChecked => {
        setAttributes({
          publishStartDate: isChecked ? new Date().toISOString() : undefined
        });
      }
    }), !!publishStartDate && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "visi-bloc-datepicker-wrapper"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.DateTimePicker, {
      currentDate: publishStartDate,
      onChange: newDate => setAttributes({
        publishStartDate: newDate
      }),
      is12Hour: false
    })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CheckboxControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Définir une date de fin', 'visi-bloc-jlg'),
      checked: !!publishEndDate,
      onChange: isChecked => {
        setAttributes({
          publishEndDate: isChecked ? new Date().toISOString() : undefined
        });
      }
    }), !!publishEndDate && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "visi-bloc-datepicker-wrapper"
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.DateTimePicker, {
      currentDate: publishEndDate,
      onChange: newDate => setAttributes({
        publishEndDate: newDate
      }),
      is12Hour: false
    })))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Visibilité par Rôle', 'visi-bloc-jlg'),
      initialOpen: false
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)("N'afficher que pour les rôles sélectionnés. Laisser vide pour afficher à tout le monde.", 'visi-bloc-jlg')), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CheckboxControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Visiteurs Déconnectés', 'visi-bloc-jlg'),
      checked: visibilityRoles.includes('logged-out'),
      onChange: isChecked => onRoleChange(isChecked, 'logged-out')
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CheckboxControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('Utilisateurs Connectés (tous)', 'visi-bloc-jlg'),
      checked: visibilityRoles.includes('logged-in'),
      onChange: isChecked => onRoleChange(isChecked, 'logged-in')
    }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), Object.entries(VisiBlocData.roles).map(([slug, name]) => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.CheckboxControl, {
      key: slug,
      label: name,
      checked: visibilityRoles.includes(slug),
      onChange: isChecked => onRoleChange(isChecked, slug)
    }))))));
  };
}, 'withVisibilityControls');
function addEditorCanvasClasses(props, block) {
  if (block.name !== 'core/group' || !block.attributes) {
    return props;
  }
  const {
    isHidden
  } = block.attributes;
  const newClasses = [props.className, isHidden ? 'bloc-editeur-cache' : ''].filter(Boolean).join(' ');
  return {
    ...props,
    className: newClasses
  };
}
function addSaveClasses(extraProps, blockType, attributes) {
  if (blockType.name !== 'core/group' || !attributes) {
    return extraProps;
  }
  const {
    deviceVisibility
  } = attributes;
  const newClasses = [extraProps.className, deviceVisibility && deviceVisibility !== 'all' ? `vb-${deviceVisibility}` : ''].filter(Boolean).join(' ');
  return {
    ...extraProps,
    className: newClasses
  };
}
function syncListView() {
  const {
    getBlockOrder
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_7__.select)('core/block-editor');
  const clientIds = getBlockOrder();
  if (!clientIds.length) {
    return;
  }
  clientIds.forEach(clientId => {
    const block = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_7__.select)('core/block-editor').getBlock(clientId);
    if (!block || block.name !== 'core/group') {
      return;
    }
    const row = document.querySelector(`.block-editor-list-view__block[data-block="${clientId}"]`);
    if (row) {
      if (block.attributes.isHidden) {
        row.classList.add('bloc-editeur-cache');
      } else {
        row.classList.remove('bloc-editeur-cache');
      }
    }
  });
}
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.addFilter)('blocks.registerBlockType', 'visi-bloc-jlg/add-visibility-attributes', addVisibilityAttributesToGroup);
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.addFilter)('editor.BlockEdit', 'visi-bloc-jlg/with-visibility-controls', withVisibilityControls);
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.addFilter)('blocks.getSaveContent.extraProps', 'visi-bloc-jlg/add-save-classes', addSaveClasses);
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_1__.addFilter)('editor.BlockListBlock.props', 'visi-bloc-jlg/add-editor-canvas-classes', addEditorCanvasClasses);
(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_7__.subscribe)(syncListView);
})();

/******/ })()
;