/*
* Overlay v2.0.2 Copyright (c) 2019 AJ Savino
* https://github.com/koga73/Overlay
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/

var Overlay = (function(){
	var _instance;

	var _events = {
		EVENT_BEFORE_SHOW:"beforeshow",
		EVENT_AFTER_SHOW:"aftershow",
		EVENT_BEFORE_HIDE:"beforehide",
		EVENT_AFTER_HIDE:"afterhide"
	};

	var _consts = {
		ID_CONTAINER:"overlayContainer",
		ID_BACKGROUND:"overlayBackground",
		ID_FRAME:"overlayFrame",
		ID_CLOSE:"overlayClose",

		CLASS_FRAME_VISIBLE:"visible",
		CLASS_BODY_OVERLAY_VISIBLE:"overlay-visible",
		CLASS_CONTENT:"overlay-content",

		DATA_CONTAINER:"data-overlay-container",
		DATA_PAGE_WRAP:"data-overlay-page-wrap",
		DATA_TRIGGER:"data-overlay-trigger",
		DATA_WIDTH:"data-overlay-width",
		DATA_HEIGHT:"data-overlay-height",
		DATA_OFFSET_X:"data-overlay-offset-x",
		DATA_OFFSET_Y:"data-overlay-offset-y",
		DATA_CONTAINER_CLASS:"data-overlay-container-class",
		DATA_USER_CLOSABLE:"data-overlay-user-closable",

		FOCUSABLE:"a[href],input,select,textarea,button,[tabindex]"
	};

	var _vars = {
		container:null,
		pageWrap:null,

		_container:null,
		_background:null,
		_content:null,
		_frame:null,
		_close:null,

		_showCallback:null,
		_hideCallback:null,

		_lastFocus:null
	};

	var _methods = {
		init:function(){
			var container = document.createElement("div");
			container.setAttribute("id", _consts.ID_CONTAINER);
			_vars._container = container;

			var background = document.createElement("div");
			background.setAttribute("id", _consts.ID_BACKGROUND);
			_vars._background = background;

			var frame = document.createElement("div");
			frame.setAttribute("id", _consts.ID_FRAME);
			_vars._frame = frame;

			var close = document.createElement("button");
			close.setAttribute("id", _consts.ID_CLOSE);
			close.setAttribute("type", "button");
			close.innerHTML = "Close";
			_vars._close = close;

			frame.appendChild(close);
			container.appendChild(background);
			container.appendChild(frame);

			var container = document.querySelector("[" + _consts.DATA_CONTAINER + "]");
			if (container){
				_instance.container = container;
			}
			var pageWrap = document.querySelector("[" + _consts.DATA_PAGE_WRAP + "]");
			if (pageWrap){
				_instance.pageWrap = pageWrap;
			}

			var anchorTriggers = document.querySelectorAll("[" + _consts.DATA_TRIGGER + "]");
			var anchorTriggersLen = anchorTriggers.length;
			for (var i = 0; i < anchorTriggersLen; i++){
				(function(i){
					var anchorTrigger = anchorTriggers[i];
					var id = anchorTrigger.getAttribute(_consts.DATA_TRIGGER);
					if (!id.length){
						id = anchorTrigger.getAttribute("href");
					}
					if (id.substr(0, 1) == "#"){
						id = id.substr(1, id.length - 1);
					}
					OOP.addEventListener(anchorTrigger, "click", function(evt){
						if (typeof evt.preventDefault !== typeof undefined){
							evt.preventDefault();
						} else {
							evt.returnValue = false;
						}
						_instance.show(id);
						return false;
					});
				})(i);
			}
		},

		destroy:function(){
			$(document.body).removeClass(_consts.CLASS_BODY_OVERLAY_VISIBLE);

			OOP.removeEventListener(document, "focusin", _methods._handler_document_focusin);
			OOP.removeEventListener(document, "keyup", _methods._handler_document_keyUp);

			var close = _vars._close;
			if (close){
				OOP.removeEventListener(close, "click", _methods._handler_close_click);
				close.removeAttribute("disabled");
			}
			_vars._close = null;

			_vars._frame = null;

			var content = _vars._content;
			if (content){
				content.parentNode.removeChild(content);
				if (typeof content._overlayData.width !== typeof undefined){
					content.style.width = ""; //content._overlayData.width;
				}
				if (typeof content._overlayData.height !== typeof undefined){
					content.style.height = ""; //content._overlayData.height;
				}
				if (typeof content._overlayData.parent !== typeof undefined){
					if (content._overlayData.parent){
						content._overlayData.parent.appendChild(content);
					}
				}
				content._overlayData = null;
				content.removeAttribute("tabindex");
				$(content).removeClass(_consts.CLASS_CONTENT);
			}
			_vars._content = null;

			var background = _vars._background;
			if (background){
				OOP.removeEventListener(background, "click", _methods._handler_background_click);
			}
			_vars._background = null;

			var container = _vars._container;
			if (container){
				container.setAttribute("class", "");
				if (container.parentNode){
					container.parentNode.removeChild(container);
				}
			}
			_vars._container = null;

			_vars._showCallback = null;
			_vars._hideCallback = null;
			_vars._lastFocus = null;

			var anchorTriggers = document.querySelectorAll("[" + _consts.DATA_TRIGGER + "]");
			var anchorTriggersLen = anchorTriggers.length;
			for (var i = 0; i < anchorTriggersLen; i++){
				var anchorTrigger = anchorTriggers[i];
				var id = anchorTrigger.getAttribute(_consts.DATA_TRIGGER);
				if (!id.length){
					id = anchorTrigger.getAttribute("href");
				}
				if (id.substr(0, 1) == "#"){
					id = id.substr(1, id.length - 1);
				}
				OOP.removeEventListener(anchorTrigger, "click");
			}
		},

		show:function(content, options, callback){
			//If string then grab from DOM otherwise it is a dynamic element passed in
			var isStatic = OOP.isString(content);
			if (typeof options == typeof undefined){
				options = {};
			}
			_vars._showCallback = callback;
			$(document.body).addClass(_consts.CLASS_BODY_OVERLAY_VISIBLE);
			OOP.dispatchEvent(_instance, new OOP.Event(_instance.EVENT_BEFORE_SHOW));

			//Hide current
			if (_vars._content != null){
				_instance.hide(function(){ //Callback chaining
					_instance.show(content, options, callback);
				});
				return;
			}

			//Parse parameters. Method parameters take priority over data attributes on content
			var content = (isStatic) ? document.getElementById(content) : content;
			var width, height, offsetX, offsetY;
			var containerClass = "";
			var userClosable = true;
			if (typeof options.width !== typeof undefined){
				width = options.width;
			} else {
				var dataWidth = content.getAttribute(_consts.DATA_WIDTH);
				if (dataWidth && dataWidth.length){
					width = dataWidth;
				}
			}
			if (typeof options.height !== typeof undefined){
				height = options.height;
			} else {
				var dataHeight = content.getAttribute(_consts.DATA_HEIGHT);
				if (dataHeight && dataHeight.length){
					height = dataHeight;
				}
			}
			if (typeof options.offsetX !== typeof undefined){
				offsetX = options.offsetX;
			} else {
				var dataOffsetX = content.getAttribute(_consts.DATA_OFFSET_X);
				if (dataOffsetX && dataOffsetX.length){
					offsetX = dataOffsetX;
				}
			}
			if (typeof options.offsetY !== typeof undefined){
				offsetY = options.offsetY;
			} else {
				var dataOffsetY = content.getAttribute(_consts.DATA_OFFSET_Y);
				if (dataOffsetY && dataOffsetY.length){
					offsetY = dataOffsetY;
				}
			}
			if (typeof options.containerClass !== typeof undefined){
				containerClass = options.containerClass;
			} else {
				var dataContainerClass = content.getAttribute(_consts.DATA_CONTAINER_CLASS);
				if (dataContainerClass && dataContainerClass.length){
					containerClass = dataContainerClass;
				}
			}
			if (typeof options.userClosable !== typeof undefined){
				userClosable = options.userClosable === true;
			} else {
				var dataUserClosable = content.getAttribute(_consts.DATA_USER_CLOSABLE);
				if (dataUserClosable && dataUserClosable.length){
					userClosable = dataUserClosable.toLowerCase() == "true";
				}
			}

			//Cache
			var container = _vars._container;
			var background = _vars._background;
			var frame = _vars._frame;
			var close = _vars._close;

			//Set frame dimensions to content dimensions and apply parameter overrides
			$(content).addClass(_consts.CLASS_CONTENT);
			content.setAttribute("tabindex", "0");
			content._overlayData = content._overlayData || {};
			if (typeof width === typeof undefined){
				if (typeof document.documentElement.currentStyle !== typeof undefined){ //IE
					width = content.currentStyle.width;
				} else {
					width = window.getComputedStyle(content).width;
				}
			}
			if (parseInt(width)){
				frame.style.width = width;
				content._overlayData.width = width;
				content.style.width = "100%";
			} else {
				frame.style.width = "";
			}
			if (typeof height === typeof undefined){
				if (typeof document.documentElement.currentStyle !== typeof undefined){ //IE
					height = content.currentStyle.height;
				} else {
					height = window.getComputedStyle(content).height;
				}
			}
			if (parseInt(height)){
				frame.style.height = height;
				content._overlayData.height = height;
				content.style.height = "100%";
			} else {
				frame.style.height = "";
			}
			if (typeof offsetX !== typeof undefined){
				frame.style.left = offsetX;
			} else {
				frame.style.left = "";
			}
			if (typeof offsetY !== typeof undefined){
				frame.style.top = offsetY;
			} else {
				frame.style.top = "";
			}
			_vars._content = content;

			//Wire events
			OOP.addEventListener(document, "focusin", _methods._handler_document_focusin);
			if (userClosable){
				OOP.addEventListener(document, "keyup", _methods._handler_document_keyUp);
				OOP.addEventListener(background, "click", _methods._handler_background_click);
				OOP.addEventListener(close, "click", _methods._handler_close_click);
			} else {
				close.setAttribute("disabled", "disabled");
			}

			//Append content
			content._overlayData.parent = content.parentNode;
			if (isStatic){
				content.parentNode.removeChild(content);
			}
			frame.insertBefore(content, close);

			//Append container
			if (!_instance.container){
				if (document.body){
					_instance.container = document.body;
				} else {
					throw new Error("Container is undefined");
				}
			}
			var appendContainer = _instance.container;
			if (_instance.container.length){
				appendContainer = _instance.container[0];
			}
			appendContainer.appendChild(container);

			//Save focus, accessibility focus trap, set focus to first focusable item
			_vars._lastFocus = document.activeElement;
			var pageWrap = _instance.pageWrap;
			if (pageWrap){
				if (pageWrap.contains(appendContainer)){
					throw "Error: The page wrapper [" + _consts.DATA_PAGE_WRAP + "] should not contain the modal container. They should be siblings instead.";
				} else {
					pageWrap.setAttribute("aria-hidden", "true");
					pageWrap.setAttribute("tabindex", "-1");
				}
			}
			var focusable = null; //content.querySelector(_consts.FOCUSABLE);
			if (!focusable){
				focusable = content;
			}
			focusable.focus();

			//Add containerClass
			$(container).addClass(containerClass);
			var timeout = setTimeout(function(){ //Delay needed for transition to render
				clearTimeout(timeout);
				$(container).addClass(_consts.CLASS_FRAME_VISIBLE);
			}, 50);

			//Wait for transition before completing show
			if (TransitionHelper.hasTransition(background) || TransitionHelper.hasTransition(frame)){
				TransitionHelper.offTransitionComplete(container);
				TransitionHelper.onTransitionComplete(container, _methods._handler_show_complete);
			} else {
				_methods._handler_show_complete();
			}
		},

		hide:function(callback){
			_vars._hideCallback = callback;
			OOP.dispatchEvent(_instance, new OOP.Event(_instance.EVENT_BEFORE_HIDE));

			//Unwire events
			OOP.removeEventListener(document, "focusin", _methods._handler_document_focusin);
			OOP.removeEventListener(document, "keyup", _methods._handler_document_keyUp);

			var close = _vars._close;
			if (close){
				OOP.removeEventListener(close, "click", _methods._handler_close_click);
				close.removeAttribute("disabled");
			}
			var background = _vars._background;
			if (background){
				OOP.removeEventListener(background, "click", _methods._handler_background_click);
			}

			//Wait for transition before completing hide
			var frame = _vars._frame;
			var container = _vars._container;
			if (container){
				if (TransitionHelper.hasTransition(background) || TransitionHelper.hasTransition(frame)){
					TransitionHelper.offTransitionComplete(container);
					TransitionHelper.onTransitionComplete(container, _methods._handler_hide_complete);
				} else {
					_methods._handler_hide_complete();
				}
				$(container).removeClass(_consts.CLASS_FRAME_VISIBLE);
			}
		},

		_handler_show_complete:function(){
			TransitionHelper.offTransitionComplete(_vars._container);

			OOP.dispatchEvent(_instance, new OOP.Event(_instance.EVENT_AFTER_SHOW));
			var showCallback = _vars._showCallback;
			if (typeof showCallback !== typeof undefined){
				showCallback();
				_vars._showCallback = null;
			}
		},

		_handler_hide_complete:function(){
			var container = _vars._container;
			TransitionHelper.offTransitionComplete(container);

			//Reset content
			var content = _vars._content;
			if (content){
				content.parentNode.removeChild(content);
				if (typeof content._overlayData.width !== typeof undefined){
					content.style.width = ""; //content._overlayData.width;
				}
				if (typeof content._overlayData.height !== typeof undefined){
					content.style.height = ""; //content._overlayData.height;
				}
				if (typeof content._overlayData.parent !== typeof undefined){
					if (content._overlayData.parent){
						content._overlayData.parent.appendChild(content);
					}
				}
				content._overlayData = null;
				content.removeAttribute("tabindex");
				$(content).removeClass(_consts.CLASS_CONTENT);
			}
			_vars._content = null;

			//Remove container
			container.setAttribute("class", "");
			container.parentNode.removeChild(container);

			//Remove accessibility focus trap and restore focus
			var pageWrap = _instance.pageWrap;
			if (pageWrap){
				pageWrap.removeAttribute("aria-hidden", "true");
				pageWrap.removeAttribute("tabindex", "-1");
			}
			var lastFocus = _vars._lastFocus;
			if (lastFocus){
				lastFocus.focus();
			}
			_vars._lastFocus = null;

			$(document.body).removeClass(_consts.CLASS_BODY_OVERLAY_VISIBLE);
			OOP.dispatchEvent(_instance, new OOP.Event(_instance.EVENT_AFTER_HIDE));
			var hideCallback = _vars._hideCallback;
			if (typeof hideCallback !== typeof undefined){
				hideCallback();
				_vars._hideCallback = null;
			}
		},

		_handler_close_click:function(evt){
			if (typeof evt.preventDefault !== typeof undefined){
				evt.preventDefault();
			} else {
				evt.returnValue = false;
			}
			_instance.hide();
			return false;
		},

		_handler_background_click:function(evt){
			if (typeof evt.preventDefault !== typeof undefined){
				evt.preventDefault();
			} else {
				evt.returnValue = false;
			}
			_instance.hide();
			return false;
		},

		_handler_document_keyUp:function(evt){
			if (evt.keyCode == 27){ //Escape
				_instance.hide();
			}
		},

		_handler_document_focusin:function(evt){
			var target = evt.target || evt.srcElement;
			var frame = _vars._frame;
			if (target != frame && !frame.contains(target)){
				if (typeof evt.stopPropagation !== typeof undefined){
					evt.stopPropagation();
				} else {
					evt.cancelBubble = true;
				}
				var content = _vars._content;
				if (content){
					content.focus();
				}
			}
		}
	};

	var TransitionHelper = (function(){
		var transitionEvent = null;
		var transitionPrefix = null;

		var transitionEvents = [["webkitTransition","webkitTransitionEnd","-webkit-"], ["transition","transitionend",""]];
		var transitionEventsLen = transitionEvents.length;
		for (var i = 0; i < transitionEventsLen; i++){
			if (typeof document.documentElement.style[transitionEvents[i][0]] !== typeof undefined){
				break;
			}
		}
		if (i != transitionEventsLen){
			transitionEvent = transitionEvents[i][1];
			transitionPrefix = transitionEvents[i][2];
		} //else not supported

		return {
			hasTransition:function(element){
				if (typeof document.documentElement.currentStyle !== typeof undefined){ //IE
					return parseFloat(element.currentStyle[transitionPrefix + "transition-duration"]) > 0;
				} else {
					return parseFloat(window.getComputedStyle(element)[transitionPrefix + "transition-duration"]) > 0;
				}
			},
			onTransitionComplete:function(element, callback){
				if (transitionEvent){
					OOP.addEventListener(element, transitionEvent, callback);
				} else {
					callback();
				}
			},
			offTransitionComplete:function(element, callback){
				if (transitionEvent){
					if (typeof callback !== typeof undefined){
						OOP.removeEventListener(element, transitionEvent, callback);
					} else {
						OOP.removeEventListener(element, transitionEvent);
					}
				}
			}
		};
	})();
	/*
	var ClassHelper = (function(){
		//Trim shim
		if (typeof String.prototype.trim !== 'function'){
			String.prototype.trim = function(){
				return this.replace(/^\s+|\s+$/g, '');
			}
		}

		return {
			addClass:function(element, classes){
				var elementClasses = (element.getAttribute("class") || "").split(" ");
				classes = classes.split(" ");
				for (var className in classes){
					elementClasses.push(classes[className].trim());
				}
				element.setAttribute("class", elementClasses.join(" ").trim());
			},

			removeClass:function(element, classes){
				var elementClasses = (element.getAttribute("class") || "").split(" ");
				classes = classes.split(" ");
				for (var className in classes){
					var elementClassesLen = elementClasses.length;
					for (var i = 0; i < elementClassesLen; i++){
						if (elementClasses[i] == classes[className].trim()){
							elementClasses.splice(i, 1);
							elementClassesLen--;
							i--;
						}
					}
				}
				element.setAttribute("class", elementClasses.join(" ").trim());
			},

			hasClass:function(element, classes){
				var elementClasses = (element.getAttribute("class") || "").split(" ");
				classes = classes.split(" ");
				var hasCount = 0;
				for (var className in classes){
					var elementClassesLen = elementClasses.length;
					for (var i = 0; i < elementClassesLen; i++){
						if (elementClasses[i] == classes[className].trim()){
							hasCount++;
							break;
						}
					}
				}
				if (hasCount == classes.length){
					return true;
				} else {
					return false
				}
			}
		};
	})();
	*/
	_instance = {
		EVENT_BEFORE_SHOW:_events.EVENT_BEFORE_SHOW,
		EVENT_AFTER_SHOW:_events.EVENT_AFTER_SHOW,
		EVENT_BEFORE_HIDE:_events.EVENT_BEFORE_HIDE,
		EVENT_AFTER_HIDE:_events.EVENT_AFTER_HIDE,

		container:_vars.container,
		pageWrap:_vars.pageWrap,

		init:_methods.init,
		destroy:_methods.destroy,
		hide:_methods.hide,
		show:_methods.show
	};
	if (document.body){
		_instance.init();
	}
	return _instance;
})();