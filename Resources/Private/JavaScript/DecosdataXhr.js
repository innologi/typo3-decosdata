(function() {
	// @TODO minify
	// @TODO refactor into modules

	/*********************/
	/* BASIC XHR SUPPORT */
	/*********************/

	if (!window.XMLHttpRequest) {
		console.warn('[decosdata] no xhr support detected, cannot enable xhr features');
		return;
	}

	// @LOW what if we can cache results through LocalStorage, or otherwise SessionStorage, with a lifetime of e.g. 1 hour?
	var dataCache = [];

	/**
	 * XHR request
	 *
	 * @param string method
	 * @param string url
	 * @param FormData data
	 * @param string cacheKey
	 * @param object ondata
	 * @param object onend
	 * @return void
	 */
	function xhrRequest(method, url, data, cacheKey, ondata, onend) {
		if (cacheKey !== null) {
			cacheKey += '-' + url;
			if (dataCache[cacheKey]) {
				console.info('[decosdata] data processing from cache');
				if (ondata !== null) {
					ondata(dataCache[cacheKey]);
				}
				if (onend !== null) {
					onend(dataCache[cacheKey]);
				}
				return;
			}
		}

		// @TODO maybe we can elevate some caching to the browser with cache control headers?
		var xhr = new XMLHttpRequest();
		xhr.open(method, url, true);
		xhr.responseType = 'json';
		xhr.onload = function () {
			if (this.status !== 200) {
				console.error('[decosdata] ' + this.status + ': ' + this.statusText);
				console.info(this);
			}
			if (this.response) {
				var response = this.response;
				// IE doesn't automatically parse responseType json
				if (typeof(response) !== 'object') response = JSON.parse(response);
				if (response.data) {
					dataCache[cacheKey] = response;
					if (ondata !== null) {
						ondata(response);
					}
					console.info('[decosdata] data processing success')
				} else {
					console.error('[decosdata] no valid xhr json response');
					console.info(response);
				}
			} else {
				console.error('[decosdata] no valid xhr json response');
				console.info(this);
			}
		};
		xhr.onerror = function() {
			// @TODO test if getting here this also still does an onload
			console.error('[decosdata] failed to execute xhr');
			console.info(this);
		};
		xhr.ontimeout = function() {
			console.error('[decosdata] xhr timeout');
			console.info(this);
		};
		xhr.onloadend = function() {
			if (onend !== null) {
				onend(this);
			}
		};
		// @TODO errors should provide visual cue, and probably restore non-xhr process
		xhr.send(data);
	}


	/******************/
	/* IE11 POLYFILLS */
	/******************/

	// @see https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove
	// from:https://github.com/jserz/js_piece/blob/master/DOM/ChildNode/remove()/remove().md
	(function(arr) {
		arr.forEach(function(item) {
			if (item.hasOwnProperty('remove')) {
				return;
			}
			Object.defineProperty(item, 'remove', {
				configurable : true,
				enumerable : true,
				writable : true,
				value : function remove() {
					if (this.parentNode !== null)
						this.parentNode.removeChild(this);
				}
			});
		});
	})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

	// @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/from#Polyfill
	// Production steps of ECMA-262, Edition 6, 22.1.2.1
	if (!Array.from) {
		Array.from = (function() {
			var toStr = Object.prototype.toString;
			var isCallable = function(fn) {
				return typeof fn === 'function'
						|| toStr.call(fn) === '[object Function]';
			};
			var toInteger = function(value) {
				var number = Number(value);
				if (isNaN(number)) {
					return 0;
				}
				if (number === 0 || !isFinite(number)) {
					return number;
				}
				return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
			};
			var maxSafeInteger = Math.pow(2, 53) - 1;
			var toLength = function(value) {
				var len = toInteger(value);
				return Math.min(Math.max(len, 0), maxSafeInteger);
			};

			// The length property of the from method is 1.
			return function from(arrayLike/* , mapFn, thisArg */) {
				// 1. Let C be the this value.
				var C = this;

				// 2. Let items be ToObject(arrayLike).
				var items = Object(arrayLike);

				// 3. ReturnIfAbrupt(items).
				if (arrayLike == null) {
					throw new TypeError(
							'Array.from requires an array-like object - not null or undefined');
				}

				// 4. If mapfn is undefined, then let mapping be false.
				var mapFn = arguments.length > 1 ? arguments[1]
						: void undefined;
				var T;
				if (typeof mapFn !== 'undefined') {
					// 5. else
					// 5. a If IsCallable(mapfn) is false, throw a TypeError
					// exception.
					if (!isCallable(mapFn)) {
						throw new TypeError(
								'Array.from: when provided, the second argument must be a function');
					}

					// 5. b. If thisArg was supplied, let T be thisArg; else let
					// T be undefined.
					if (arguments.length > 2) {
						T = arguments[2];
					}
				}

				// 10. Let lenValue be Get(items, "length").
				// 11. Let len be ToLength(lenValue).
				var len = toLength(items.length);

				// 13. If IsConstructor(C) is true, then
				// 13. a. Let A be the result of calling the [[Construct]]
				// internal method
				// of C with an argument list containing the single item len.
				// 14. a. Else, Let A be ArrayCreate(len).
				var A = isCallable(C) ? Object(new C(len)) : new Array(len);

				// 16. Let k be 0.
				var k = 0;
				// 17. Repeat, while k < len… (also steps a - h)
				var kValue;
				while (k < len) {
					kValue = items[k];
					if (mapFn) {
						A[k] = typeof T === 'undefined' ? mapFn(kValue, k)
								: mapFn.call(T, kValue, k);
					} else {
						A[k] = kValue;
					}
					k += 1;
				}
				// 18. Let putStatus be Put(A, "length", len, true).
				A.length = len;
				// 20. Return A.
				return A;
			};
		}());
	}



	/****************/
	/* DATA PARSING */
	/****************/

	/**
	 * Formats raw data into usable HTML
	 *
	 * @param array data
	 * @return string
	 */
	function getDataHtml(itemTemplate, data) {
		var newData = '';
		//for (var item of data) {
		data.forEach(function(item) {
			var contentElements = itemTemplate.getElementsByClassName('content');
			//for (var content of contentElements) {
			Array.from(contentElements).forEach(function(content) {
				if (content.dataset.cid && item['content' + content.dataset.cid]) {
					content.innerHTML = item['content' + content.dataset.cid];
				}
			});
			// @LOW what if I have multiple classes? using classList.toggle/add/remove is not supported on IE11
			itemTemplate.className = 'item item-' + item.id;
			newData += itemTemplate.outerHTML;
		});
		return newData;
	}

	function getItemTemplate(dataContainer) {
		if (dataContainer === null) {
			throw 'no valid data container.'
		}
		var itemElement = dataContainer.querySelector('.item');
		return itemElement === null ? null : itemElement.cloneNode(true);
	}


	/**************/
	/* XHR PAGING */
	/**************/

	// can track if given element is on screen
	var OnReach = (function() {
		var isEnabled = false,
		listenerAllowed = true,
		onReachCallback = null,
		areWeThereYet = function() {
			if (__this.elementPositionReached(__this.element)) {
				__this.disable();
				onReachCallback();
			}
		},
		listener = function(event) {
			if (listenerAllowed) {
				listenerAllowed = false;
				// https://developer.mozilla.org/en-US/docs/Web/Events/scroll
				window.requestAnimationFrame(function() {
					areWeThereYet();
					listenerAllowed = true;
				});
			}
		},
		__this = {
			element: null,
			enable: function(element, callback) {
				if (isEnabled) {
					console.warn('[decosdata] onReach-feature cannot be enabled twice!');
					return;
				}
				__this.element = element;
				onReachCallback = callback;
				isEnabled = true;
				window.addEventListener('scroll', listener);
				window.addEventListener('resize', listener);
				// we may already be there, before any scrolling or resizing :o)
				areWeThereYet();
			},
			disable: function() {
				if (isEnabled) {
					window.removeEventListener('scroll', listener);
					window.removeEventListener('resize', listener);
					isEnabled = false;
				}
			},
			elementPositionReached: function(element) {
				var isquirks = document.compatMode !== 'BackCompat',
					// Edge likes to rebel I guess (against doing what it's supposed to do)
					page = isquirks && !/Edge/.test(navigator.userAgent) ? document.documentElement : document.body,
					viewportBottomPos = page.scrollTop + ('innerHeight' in window ? window.innerHeight : page.clientHeight),
					elemTopPos = 0;
				while (element.offsetParent !== null) {
					elemTopPos += element.offsetTop;
					element = element.offsetParent;
				}
				// pos is counted from the top, so higher number means lower position vertically
				return viewportBottomPos >= elemTopPos;
			}
		};

		return __this;
	})();

	/**
	 * XHR Pager Object constructor
	 *
	 * @param DOMNode Xhr Pager element
	 * @return XhrPager
	 */
	function XhrPager(elem) {
		// initialize elem
		this.element = elem;
		this.more = false;
		this.first = false;
		if (elem.dataset.xhr) {
			this.more = elem.dataset.xhr;
			this.first = elem.dataset.xhr;
			delete elem.dataset.xhr;
		}
		if (elem.href) elem.href = '#';


		// determine section, dataContainer and itemTemplate
		var sectionContainer = null;
		do {
			sectionContainer = elem.parentNode;
		} while ( !(sectionContainer === null || sectionContainer.classList.contains('section')) );
		if (sectionContainer === null || !sectionContainer.dataset.section) {
			throw 'xhr pager is not in anything designated "section"';
		}
		this.section = sectionContainer.dataset.section;
		var dataContainer = sectionContainer.querySelector('.items'),
			itemTemplate = getItemTemplate(dataContainer);


		// every xhr pager gets its own OnReach instance
		this.onReach = Object.create(OnReach);


		// disables the xhr pager
		this.disable = function() {
			elem.className = 'xhr-paging';
			this.onReach.disable();
		};


		// enables the xhr pager
		this.enable = function(uri) {
			if (uri === false) {
				this.disable();
				return;
			}

			elem.className = 'xhr-paging loader inactive';
			this.more = uri;

			var _that = this;
			this.onReach.enable(elem, function() {
				console.info('[decosdata] paging in reach');
				elem.className = 'xhr-paging loader active';
				xhrRequest('GET', _that.more, null, null, function(response) {
					dataContainer.innerHTML += getDataHtml(itemTemplate, response.data);
					if (response.paging) {
						_that.enable(response.paging.more);
					}
				}, null);
				// @LOW if paging does not exist, we can end up with an forever active xhr paging loader, if no onend()
			});
		};


		// enable!
		this.enable(this.more);
	}

	var xhrPagingRegister = {};

	/**
	 * Initializes (pre-)existing XHR pagers
	 *
	 * @param DOMNode container
	 * @return void
	 */
	function initXhrPagers(container) {
		var xhrPagingElements = container.querySelectorAll('.tx-decosdata .xhr-paging');
		if (xhrPagingElements.length > 0) {
			Array.from(xhrPagingElements).forEach(function(x) {
				try {
					var xhrPager = new XhrPager(x);
					// if xhrPager won't have a valid more-url, see if a previous incarnation exists and did
					if (!x.dataset.xhr && xhrPagingRegister[xhrPager.section]) {
						xhrPager.enable(xhrPagingRegister[xhrPager.section].first);
					}
					xhrPagingRegister[xhrPager.section] = xhrPager;
				} catch (e) {
					console.warn('[decosdata] ' + e);
				}
			});
		}
	}

	initXhrPagers(document);


	/**************/
	/* XHR SEARCH */
	/**************/

	/**
	 * Changes paging count number in count-elements
	 *
	 * @param array countElements
	 * @param object paging
	 * @return void
	 */
	function changePagingCount(countElements, paging) {
		if ( countElements.length > 0 && paging.resultCount !== null ) {
			// replace counts
			var newCount = parseInt(paging.resultCount);
			Array.from(countElements).forEach(function(c) {
				c.innerHTML = c.innerHTML.replace(c.dataset.count, newCount);
				c.dataset.count = newCount;
			});
		}
	}

	/**
	 * SearchForm object constructor
	 *
	 * @param DOMNode elem
	 * @param int searchDelay
	 * @param int searchAtLength
	 * @return void
	 */
	function SearchForm(elem, searchDelay, searchAtLength) {
		// initialize elem
		this.element = elem;
		if (!elem.dataset.xhr) {
			throw 'searchform is not xhr-enabled';
		}
		this.action = elem.dataset.xhr;
		delete elem.dataset.xhr;


		// hide submit button
		elem.elements['tx_decosdata[submit]'].className = 'search-submit invisible';


		// reset searchBox
		this.searchBox = elem.elements['tx_decosdata[search]'];
		// disable visual cues
		this.resetSearchBoxCues = function() {
			this.searchBox.className = 'search-box full-width';
		};
		this.resetSearchBoxCues();


		// initialize section container
		if (!elem.dataset.section) {
			// @TODO create a custom templateItem and dataElement so we can support xhr search on pages that initially show 0 data?
			throw 'no section set for search xhr';
		}
		// @LOW replace once element.closest() is fully supported on every major browser
		var sectionContainer = document.querySelector('.tx-decosdata .section-' + elem.dataset.section);
		if (sectionContainer === null) {
			throw 'search form could not find its designated "section"';
		}


		// is there already a XhrPager?
		this.xhrPager = xhrPagingRegister[elem.dataset.section] ? xhrPagingRegister[elem.dataset.section] : null;


		// create overlay for use in search submit
		var overlay = document.createElement('div');
		overlay.className = 'overlay loader active';


		// parse section
		var originalSection = null,
			pagingElements = [],
			countElements = [],
			dataContainer = null,
			itemTemplate = null;
		// parses sectionContainer
		function parseSection() {
			originalSection = sectionContainer.cloneNode(true);
			pagingElements = sectionContainer.querySelectorAll('.pagebrowser .pagebrowser-navigation');
			countElements = sectionContainer.querySelectorAll('.pagebrowser .resultcount');
			dataContainer = sectionContainer.querySelector('.items');
			itemTemplate = getItemTemplate(dataContainer);
		}
		parseSection();


		// clears paging elements
		function clearPagingElements() {
			if (pagingElements.length > 0) {
				Array.from(pagingElements).forEach(function(p) {
					p.remove();
				});
				pagingElements = [];
			}
		}


		// resets section
		this.resetSection = function() {
			console.info('[decosdata] resetting section');
			sectionContainer.parentNode.insertBefore(originalSection, sectionContainer);
			sectionContainer.remove();
			sectionContainer = originalSection;
			initXhrPagers(sectionContainer);
			this.xhrPager = xhrPagingRegister[elem.dataset.section] ? xhrPagingRegister[elem.dataset.section] : null;
			parseSection();
		};


		// define internal search vars
		var submitAllowed = true,
			lastSearchValue = '',
			searchTimeout = null,
			_that = this;


		// submits form the xhr way
		// needs to refer to _that everywhere because it's also called in EventHandler contexts
		this.submit = function() {
			// disable submit while a submit is still in progress
			if (!submitAllowed) {
				// @LOW why disable cue if a submit was still in progress?
				_that.resetSearchBoxCues();
				return false;
			}
			submitAllowed = false;

			// if same as last search: do nothing
			var searchValue = _that.searchBox.value.trim();
			if (searchValue.localeCompare(lastSearchValue) === 0) {
				_that.resetSearchBoxCues();
				submitAllowed = true;
				return false;
			}
			lastSearchValue = searchValue;

			// make sure we don't continue with an active xhr pager
			if (_that.xhrPager) {
				_that.xhrPager.disable();
			}

			// if empty search submitted: reset section
			if (searchValue.length === 0) {
				_that.resetSection();
				_that.resetSearchBoxCues();
				submitAllowed = true;
				return false;
			}

			// visual cue
			dataContainer.parentNode.insertBefore(overlay, dataContainer);
			// search request
			xhrRequest('POST', _that.action, new FormData(_that.element), searchValue, function(response) {
				// on data
				clearPagingElements();
				dataContainer.innerHTML = getDataHtml(itemTemplate, response.data);
				if (response.paging) {
					changePagingCount(countElements, response.paging);
					if (_that.xhrPager === null) {
						// create and register an xhr pager if none was bound to the searchform before
						var xhrPagingElement = document.createElement('div');
						dataContainer.parentNode.appendChild(xhrPagingElement);
						_that.xhrPager = new XhrPager(xhrPagingElement);
						xhrPagingRegister[_that.xhrPager.section] = _that.xhrPager;
					}
					_that.xhrPager.enable(response.paging.more);
				}
			}, function(response) {
				// on end
				overlay.remove();
				_that.resetSearchBoxCues();
				submitAllowed = true;
			});

			return true;
		};


		// replace default submit by our own
		elem.addEventListener('submit', function (e) {
			e.preventDefault();
			// clear previous search-in-wait if any
			clearTimeout(searchTimeout);
			// if the submit did not complete, don't pass this event on to other listeners
			if (! _that.submit() ) e.stopImmediatePropagation();
		});


		// submit on input
		function inputSearchListener(event) {
			var length = _that.searchBox.value.trim().length;
			if (length === 0 || length >= searchAtLength) {
				// visual cue
				_that.searchBox.className = 'search-box full-width loader active';
				// clear previous search-in-wait if any
				clearTimeout(searchTimeout);
				// set delay
				searchTimeout = setTimeout(_that.submit, searchDelay);
			}
		}
		// trigger on any type of input
		this.searchBox.addEventListener('input', inputSearchListener);
	}


	// currently we support only 1 searchform on a single page
	var searchForm = null;
	try {
		if (document.forms.decosdatasearch) searchForm = new SearchForm(document.forms.decosdatasearch, 600, 3);
	} catch (e) {
		console.warn('[decosdata] ' + e);
	}

})();