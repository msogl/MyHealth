function monthChange(form)
{
	var days = new Array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	var formdays = form.d;
	var month = form.m.value - 1;
	var selected = formdays.options.selectedIndex;

	formdays.options.length = 0;

	for (var ix = 1; ix <= days[month]; ix ++) {
		formdays.options[formdays.options.length] = new Option("" + ix, ix);
	}

	if (selected >= formdays.options.length)
		formdays.options.selectedIndex = 0;
	else
		formdays.options.selectedIndex = selected;
}

function getObj(name)
{
	if (document.getElementById)
	{
		this.obj = document.getElementById(name);
		this.style = document.getElementById(name).style;
	}
	else if (document.all)
	{
		this.obj = document.all[name];
		this.style = document.all[name].style;
	}
	else if (document.layers)
	{
		this.obj = document.layers[name];
		this.style = document.layers[name];
	}
}

function setOverlayStyle(id, modal, blurBackground)
{
	$('#'+id).height($(document).height());
	$('#'+id).width($(document).width());
	$('#'+id).css('visibility', 'visible');
	
	if (blurBackground) {
		//$('#'+id).css('background', 'url(/images/overlay.png) top right repeat');
		//$('#'+id).css('background-color','rgba(0,0,0,0.5)');
		$('#'+id).addClass('blurbackground');
	}
	else {
		//$('#'+id).css('background','none');
		$('#'+id).removeClass('blurbackground');
	}

	$('#'+id).show();

}

function showPopup(id, width, height, modal, blurBackground) {
	if (modal) {
		if (blurBackground == undefined) {
			blurBackground = true;
		}
		
		setOverlayStyle('overlay', modal, blurBackground);
	}

	$element = $('#'+id);
	$element.css({'height':'auto', 'width':'auto'});
	$element.find('.content').css({'height':'auto', 'width':'auto'});
	$element.attr('data-width', width);
	$element.attr('data-height', height);

	var winWidth = $(window).width();
	var winHeight = $(window).height();

	// adjust the width if it's larger than the window
	if (width > winWidth) {
		width = winWidth - 16;
	}

	$element.css('position', 'absolute');
	$element.css('width', width+'px');

	if (height != 'auto') {
		$element.css('height', height+'px');
	}
	else {
		height = $element.outerHeight();
	}

	// adjust the height if it's larger than the window
	if (height > winHeight) {
		height = winHeight - 16;
		$element.css('height', height+'px');
	}

	// height cannot be smaller than 80px
	if (height < 72) {
		height = 72;
		$element.css('height', height+'px');
	}

	var top = $(window).scrollTop() + ((winHeight - height) / 2);
	var left = $(window).scrollLeft() + ((winWidth - width) / 2);

	if (top < 0) {
		top = 0;
	}

	if (left < 0) {
		left = 0;
	}

	$element.css('top', top + 'px');
	$element.css('left', left + 'px');

	// ensure that content + footer is not more than the popup height (add 24 for shadow height, padding, etc.)
	var overallHeight = $element.outerHeight();
	var contentHeight = $element.find('.content').outerHeight();
	var footerHeight = $element.find('.footer').outerHeight();

	if (contentHeight == 0) {
		contentHeight = 400;
	}

	if (footerHeight == 0) {
		footerHeight = 32;
	}

	if ((contentHeight + footerHeight + 24) > overallHeight) {
		contentHeight = overallHeight - footerHeight - 24;
		$element.find('.content').css('height', contentHeight);
	}

	if (jQuery.ui) 
		$('#'+id).show("scale",{}, 230);
	else
		$('#'+id).show();

	if (typeof window.popupResizeEventDefined === 'undefined') {
		window.addEventListener('resize', function() {
			window.popupResizeEventDefined = true;
			
			$('.popup').each(function() {
				if ($(this).css('display') != 'none') {
					var id = $(this).attr('id');
					var width = $(this).attr('data-width');
					var height = $(this).attr('data-height');
					showPopup(id, width, height, false);
				}
			});
		});
	}
}

function hidePopup(id) {
	if (!document.getElementById(id))
		return;

	if (jQuery.ui)
		$('#'+id).hide("scale", function() {$('#overlay').hide()}, 250);
	else
		$('#'+id).hide(function() {$('#overlay').hide()});
}

function unescapeAndMarkdown(text) {
		return text.replace(/&lt;/g, "<")
				.replace(/&gt;/g, ">")
				.replace(/&quot;/g, '"')
				.replace(/&amp;/g, "&")
				.replace(/\[b\]/g, "<strong>")
				.replace(/\[\/b\]/g, "</strong>")
				.replace(/\[i\]/g, "<em>")
				.replace(/\[\/i\]/g, "</em>")
				.replace(/\[u\]/g, "<u>")
				.replace(/\[\/u\]/g, "</u>")
				.replace(/\r/g, "")
				.replace(/\n/g, "<br/>");
}

function basename(str)
{
	 var base = new String(str).substring(str.lastIndexOf('/') + 1); 
		if(base.lastIndexOf(".") != -1)			 
				base = base.substring(0, base.lastIndexOf("."));
	 return base;
}

function isEventSupported(eventName) {
	var el = document.createElement("video");
	eventName = 'on' + eventName;
	var isSupported = (eventName in el);
	if (!isSupported) {
		el.setAttribute(eventName, 'return;');
		isSupported = typeof el[eventName] == 'function';
	}
	el = null;
	return isSupported;
}

function uuid() {
  var uuid = "", i, random;
  for (i = 0; i < 32; i++) {
    random = Math.random() * 16 | 0;
 
    if (i == 8 || i == 12 || i == 16 || i == 20) {
      uuid += "-"
    }
    uuid += (i == 12 ? 4 : (i == 16 ? (random & 3 | 8) : random)).toString(16);
  }
  return uuid;
}

/**
 * Returns the url, including directories, without the current page
 * @param {string} url 
 */
function baseUrl()
{
  const currentUrl = document.baseURI;
  const url = new URL(currentUrl);
  const origin = url.origin;    // https://www.example.com
  let pathname = url.pathname;  // /folder1/folder2/page.html
  pathname = pathname.substring(0, pathname.lastIndexOf('/'))
  return origin + pathname
}

/**
 * Direct to specified url. The url must be a route within the current site.
 * @param {*} url 
 * @returns 
 */
function redirect(url)
{
  let base = baseUrl()
  if (url.startsWith('http') && !url.startsWith(base)) {
    console.warn(`Security: cannot redirect to external sites: ${url}`);
    return;
  }
  
  if (!url.startsWith(base)) {
    url = `${base}/${url}`;
  }

  if (url.indexOf('?') == -1) {
    window.location.href = url;
    return;
  }

  if (typeof App !== 'undefined' && !url.includes('token=')) {
    url += `&token=${App.CSRFToken}`;
  }

  window.location.href = url;
}

function redirectPost(url, asIs, newWindow)
{
	if (url.indexOf('?') == -1) {
		redirect(url);
		return false;
	}

	var URLparts = url.split('?');	//Ignore base site url only take parameters

	var formId = 'form-to-post-'+uuid();
	var form = '<form id="'+formId+'" action="'+URLparts[0]+'" method="post"'+(newWindow ? ' target="newWindow"' : '')+'>\n';
	var URLpartsarr = (URLparts[1].split("&")); //Split all the parameters

	for (var ix=0; ix<URLpartsarr.length; ix++) {
		var fieldInfo = URLpartsarr[ix].split("=");
		var value = fieldInfo[1];
		
		if (!asIs) {
			value = decodeURIComponent(fieldInfo[1].replace(/\+/g,  " "));
		}
			
		form += '<input type="hidden" name="'+fieldInfo[0]+'" value="'+value+'" />\n';
	}
	
	form += '</form>';

	$('body').append(form);
	document.getElementById(formId).submit();
	return false;
}

function redirectAfter(url, timeInMs)
{
  setTimeout(function() {
    redirect(url);
  }, timeInMs);
}

function validateEmail(email)
{
	var emailRegex = /^([A-Za-z0-9_\-.+])+@([A-Za-z0-9_\-.])+\.([A-Za-z]{2,})$/;
    return emailRegex.test(email);
}

function createElement(parentNode, element)
{
	// element is an object consisting of tag, style, text and attributes
	
	var newNode = document.createElement(element.tag);
	
	if (element.style) {
		newNode.style.cssText = element.style;
	}
	
	if (element.class) {
		newNode.className = element.class;
	}
	
	if (element.text) {
		newNodeText = document.createTextNode(element.text);
	}
		
	if (element.html) {
		newNode.innerHTML = element.html;	
	}
	
	if (element.attributes) {
		element.attributes.forEach(function(attr) {
			newNode.setAttribute(attr.attribute, attr.value);
		});
	}

	if (typeof parentNode === 'string') {
		document.getElementById(parentNode).appendChild(newNode);
	}
	else {
		parentNode.appendChild(newNode);
	}
}

function setDefaultButton(id)
{
	if (!id)
		return;
		
	if (id == 'none') {
		document.addEventListener('keydown', function(event) {
			if (event.key == 'Enter' && event.target.nodeName != 'TEXTAREA') {
				event.preventDefault();
				return false;
			}
		});
		return;
	}

	window.addEventListener("keydown", function(event){
		if (event.key == 'Enter') {
			event.preventDefault(); 
			document.getElementById(id).click();
			return false;
		}
	});	
}

function setCancelButton(id)
{
	if (!id)
		return;
		
	if (id == 'none') {
		document.addEventListener('keydown', function(event) {
			if (event.key == 'Escape' && event.target.nodeName != 'TEXTAREA') {
				event.preventDefault();
				return false;
			}
		});
		return;
	}

	window.addEventListener("keydown", function(event){
		if (event.key == 'Escape') {
			event.preventDefault(); 
			document.getElementById(id).click();
			return false;
		}
	});	
}

function scrollWindowTo(element, speed)
{
	if (speed === undefined) {
		speed = "fast";
	}
	
	if (!element.startsWith("#") && !element.startsWith(".")) {
		element = "#" + element;
	}
	
	$("html,body").animate({
		scrollTop: $(element).offset().top
	}, speed);

	return false;
}

function wait()
{
	document.querySelector('body').classList.add('wait');
}

function removeWait()
{
	document.querySelector('body').classList.remove('wait');
}

async function showLoader(selector, showWaitCursor)
{
	let loader = document.getElementById('loader-container');
	if (loader == null) {
		$('body').append('<div id="loader-container" class="center no-print"><div class="loader"></div></div>');
		loader = document.getElementById('loader-container');
	}

	if (loader.style.display != '' && loader.style.display != 'none') {
		// already displayed, so don't display again
		return;
	}

	if (typeof showWaitCursor === 'undefined' || showWaitCursor === true) {
		wait();
	}

	if (typeof selector === 'undefined') {
		selector = '#view-container';
	}

	let selWidth = $(selector).width();
	let selHeight =  $(selector).height();

	if (selHeight > $(window).height()) {
		selHeight = $(window).height();
	}

	let centerLeft = $(selector).offset().left + ((selWidth - $('#loader-container').width()) / 2);
	let centerTop = $(selector).position().top + ((selHeight - $('#loader-container').height()) / 2);
	$('#loader-container').css('left', centerLeft + 'px');
	$('#loader-container').css('top', centerTop + 'px');
	$(selector).css({
		'-webkit-filter': 'blur(5px)',
		'-moz-filter': 'blur(5px)',
		'-o-filter': 'blur(5px)',
		'-ms-filter': 'blur(5px)',
		'filter': 'blur(5px)',
	});
	$('#loader-container').show();
	await delay(50);
}

function delay(ms)
{
	// NOTE: must be used in async function.
	// Usage: await delay(50);
	return new Promise(resolve => setTimeout(resolve, ms))
}

function openwinpost(url, width, height, scrollable)
{
	// remove any previous faux forms
	$("form[target='newWindow']").each(function() {
		if ($(this).attr("id").startsWith("form-to-post-")) {
			$(this).remove();
		}
	});
	
	var URLparts = url.split('?');	//Ignore base site url only take parameters

	var formId = 'form-to-post-'+uuid();
	var form = '<form id="'+formId+'" action="'+URLparts[0]+'" method="post" target="newWindow">\n';
	
	if (url.indexOf("token=") == -1) {
		form += '<input type="hidden" name="token" value="'+App.CSRFToken+'" />\n';
	}
	
	var URLpartsarr = (URLparts[1].split("&")); //Split all the parameters
	
	for (var ix=0; ix<URLpartsarr.length; ix++) {
			var fieldInfo = URLpartsarr[ix].split("=");
		var value = fieldInfo[1];
		value = decodeURIComponent(fieldInfo[1].replace(/\+/g,	" "));
			form += '<input type="hidden" name="'+fieldInfo[0]+'" value="'+value+'" />\n';
	}
	
	form += '</form>';

	$('body').append(form);

	var left = (window.screen.width / 2) - ((width / 2) + 10);
	var top = (window.screen.height / 2) - ((height / 2) + 50);
	var winParams = "width=" + width + ",height=" + height + ",top=" + top + ",left=" + left;
	var options = winParams + ",directories=no,location=no,menubar=no,resizable=no,scrollbars="+(scrollable ? "yes" : "no")+",status=yes,toolbar=no";
	var winHandle = window.open('',"newWindow",options); 	// open blank window

	if (!winHandle)
		winHandle.opener = self;

	document.getElementById(formId).submit();
	winHandle.focus();
	return false;
	
}

async function doFetch(method, url, params, returnType) {
  if (!returnType) {
    returnType = 'json'
  }

  returnType = returnType.toLowerCase();

  let paramstring = '';

  if (typeof params !== 'undefined') {
    if (typeof params === 'string') {
      if (!params.includes('token=')) {
        params += '&token='+App.CSRFToken;
      }

      paramstring = params;
    }
    else if (!params.token && App.CSRFToken) {
      params.token = App.CSRFToken;
    }
  }

  method = method.toLowerCase();
  if (method === 'get') {
    if (typeof params !== 'string') {
      paramstring = new URLSearchParams(params);
    }
  }

  try {
    let response;

    if (method === 'get') {
      response = await fetch(url+'?'+paramstring, {
        method: 'get'
      });
    }
    else {
      const formData = new FormData();
      response = await fetch(url, {
        method: 'post',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(params)
      });
    }

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.text();

    if (returnType === 'json' && data.startsWith('{') && data.endsWith('}')) {
      return JSON.parse(data);
    }

    // In some circumstances, we will return HTML, except for errors, which are JSON
    if (returnType !== 'json' && data.startsWith('{') && data.endsWith('}')) {
      const maybe = JSON.parse(data);
      if (maybe.error) {
        return maybe;
      }
    }

    if (returnType === 'json') {
      console.log(data);
      return {
        error: 'Invalid response'
      }
    }

    return data;
  }
  catch (error) {
    console.error('Fetch error:', error);
    return {error: 'Error saving data'}
  }
}

function getCsrfToken()
{
	const token = document.querySelector('meta[name="csrf-token"]');
	return (token != null ? token.getAttribute('content') : '');
}

function computedHeight(elem) {
  return (elem == null ? 0 : parseFloat(getComputedStyle(elem, null).height.replace('px', '')));
}

function computedWidth(elem) {
  return (elem == null ? 0 : parseFloat(getComputedStyle(elem, null).width.replace('px', "")));
}

function enableElems(selector) {
  document.querySelectorAll(selector).forEach(elem => elem.disabled = false);
}

function disableElems(selector) {
  document.querySelectorAll(selector).forEach(elem => elem.disabled = true);
}

function isBrowserReload() {
  return
    performance.getEntriesByType('navigation')[0]?.type === 'reload' ||
    performance.navigation?.type === 1; // fallback for old browsers
}

/**
 * Limits input to numeric only.
 * @param {string} key - Key pressed
 * @param {bool} allowFloat - default false
 */
function isNumericKey(key, allowFloat) {
  if (key == '.' && !allowFloat) {
    return false;
  }

  return (key.match(/[0-9]/) != null);
}

function isControlKey(key, ctrlPressed) {
  let controlKeys = ['Backspace', 'Delete', 'Home', 'End', 'ArrowLeft', 'ArrowRight'];
  if (controlKeys.includes(key)) {
    return true;
  }

  // Allow for ctrl-c (copy), ctrl-v (paste), ctrl-x (cut), ctrl-a (select all)
  if (ctrlPressed && ['c', 'v', 'x' ,'a'].includes(key)) {
    return true;
  }

  return false;
}
