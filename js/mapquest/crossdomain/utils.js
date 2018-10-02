if (!Array.prototype.push) Array.prototype.push = function() {
   for (var i=0; i<arguments.length; i++) this[this.length] = arguments[i];
   return this.length;
}

function mq_ParamExists (varname) {
   var undef;
   return (varname !== undef);
}


/**
 * =GET ELEMENT BY ID
 */
function getElementById(fId)
{
   if(document.getElementById(fId))
   {
      return document.getElementById(fId);
   }
    return null;
} //getElementById(fId)


/*******************************************************************************/
/* OAPI Functions                                                              */
/*******************************************************************************/
//function used to update a script node used for remote data calls.
//takes a string of the query data and string of the div name

function mqDoRemote(strQueryData, strDivName, strParentTagName)
{
   var parent      = document.getElementsByTagName(strParentTagName).item(0);
   var scMQRemote   = document.getElementById(strDivName);
   if(scMQRemote)
   {
      parent.removeChild(scMQRemote);
   }
   scMQRemote          = document.createElement("script");
   var srcString = "http://" + _mqServerPort + "/oapi/transaction?" + strQueryData + "&key=" + _mqKey;
   var maxLength = 2048;
   var dispLength = 2;
   if (g_mqbrowserinfo.isNS) {
      maxLength = 8192;
      dispLength = 8;
   }
   if (srcString.length > maxLength) {
      alert("The request query exceeds the limit ("+dispLength+" Kb) allowed for your browser type. Please reduce the amount of data in the request query!");
      return;
   }
   scMQRemote.src = srcString;
   scMQRemote.type     = "text/javascript";
   scMQRemote.id       = strDivName;
   parent.appendChild(scMQRemote);
}//loadJS()


//crossbrowser wrapper to create an xml document object
//from a given string
function mqCreateXMLDoc(strXML) {
   var newDoc;

   if (document.implementation.createDocument){
      // Mozilla, create a new DOMParser
      var parser = new DOMParser();
      //escaping & for safari -start
      if(g_mqbrowserinfo.isSafari)
         strXML = strXML.replace( /&/g,'&amp;');
      //escaping & for safari -stop
      newDoc = parser.parseFromString(strXML, "text/xml");
   } else if (window.ActiveXObject){
      // Internet Explorer, create a new XML document using ActiveX
      // and use loadXML as a DOM parser.
      newDoc = new ActiveXObject("Microsoft.XMLDOM");
      newDoc.async="false";
      newDoc.loadXML(strXML);
   }
   return newDoc;
}


//crossbrowser wrapper to create an xml document object
//from a node
function mqCreateXMLDocFromNode(ndNewRoot) {
   var newDoc;

   if (document.implementation.createDocument){
      var newDoc = document.implementation.createDocument("", "", null);
      newDoc.importNode(ndNewRoot,true);
   } else if (window.ActiveXObject){
      // Internet Explorer, create a new XML document using ActiveX
      // and use loadXML as a DOM parser.
      newDoc = new ActiveXObject("Microsoft.XMLDOM");
      newDoc.async="false";
      newDoc.loadXML(ndNewRoot.xml);
   }
   return newDoc;
}

//crossbrowser wrapper to convert an xml document object into a string
function mqXmlToStr(xmlDoc) {
   var strXml = new String;

   if (xmlDoc == null) return "";

   if (g_mqbrowserinfo.isNS) {
      var serializer = new XMLSerializer();
      strXml = serializer.serializeToString(xmlDoc);
   } else if (g_mqbrowserinfo.isIE) {
      strXml = xmlDoc.xml;
   }
   if(g_mqbrowserinfo.isSafari)
   {
      var serializer = new XMLSerializer();
      strXml = serializer.serializeToString(xmlDoc);
      //un-escaping & for safari -start
      strXml = strXml.replace( /#38;/g,'&');
      //escaping & for safari -stop
   }
   return strXml;
}


//crossbrowser wrapper used to return a node object given a specified xpath expression:
//ie xml doc <location><address></address></location>
//xpath expression /location/address
//will return a pointer to the address node
function mqGetNode(xmlDoc, strPath) {

   var node;
   //Safari adaptation start--
   if (g_mqbrowserinfo.isSafari)
   {
      var names = new Array();
      names = strPath.split('/');

      if(names[names.length-1].indexOf('@') != -1)
      {
         names.splice(names.length-1,1);
      }
      var tree = xmlDoc.documentElement;
      var isfound = false;
      if( names.length == 2 && tree.tagName == names[1])
         isfound = true;
      else
      {
         for(var i=1; i<names.length -1 ; i++)
         {
            isfound = false;
            if(tree.tagName == names[i] && tree.hasChildNodes())
            {
               var nodes=(tree.hasChildNodes())?tree.childNodes.length:0;
               for(var j=0; j<nodes; j++)
               {
                  if(tree.childNodes[j].tagName == names[i+1])
                  {
                     tree = tree.childNodes[j];
                     isfound = true;
                     break;
                  }
               }
            }
            if (names[i+1] && names[i+1].indexOf('text()') != -1)
            {
               isfound = true;
            }
            if(names[i + 1].indexOf('[') != -1)
            {
               var index = parseInt(names[i+1].substr(names[i+1].indexOf('[')+1,names[i+1].indexOf(']')-1));
               names[i+1] = names[i+1].substr(0,names[i+1].indexOf('['));
               tree = xmlDoc.getElementsByTagName(names[i+1]).item(index -1);//-1 for safari
               isfound = true;
            }
         }
      }
      node = (isfound==true)? tree: null;
      return node;
   }
   //Safari adaptation stop--
   else if (g_mqbrowserinfo.isIE) {
      node = xmlDoc.selectSingleNode(strPath);
      return node;
   } else if (g_mqbrowserinfo.isNS) {
      node = xmlDoc.evaluate(strPath, xmlDoc, null, 9, null);
      return node.singleNodeValue;
   }

   return null;
}


//crossbrowser wrapper used to return the text of a given node
//ie loop on all <request> nodes in xml to change text
// document.getElementsByTagName("request");
function mqGetNodeText(domNode) {
   var elemText = "";
   if (g_mqbrowserinfo.isIE) {
      elemText = domNode.text;
   } else if (g_mqbrowserinfo.isNS && domNode.firstChild) {//without domNode.firstChild condition giving error in Mac(safari).
      elemText = domNode.firstChild.nodeValue;
   }
   if(g_mqbrowserinfo.isSafari && domNode.firstChild) {//without domNode.firstChild condition giving error in Mac(safari).
      elemText = domNode.firstChild.nodeValue;
      // for Safari only elemText was null in some cases
      elemText = (elemText ? elemText:"");
      //un-escaping & for safari -start
      elemText = elemText.replace( /#38;/g,'&');
      //un-escaping & for safari -stop
   }
   return elemText;
}

//crossbrowser wrapper used to return the text of a given node given a specified xpath expression:
//ie xml doc <location><address>122 N Plum St.</address></location>
//xpath expression /location/address
//will return the string "122 N Plum St." or
//ie xml doc <locationCollection count="3">....</locationCollection>
//xpath expression /locationCollection/@count
//will return a value of 3

function mqGetXPathNodeText(xmlDoc, strPath) {
   var node;
   //Safari adaptation start--
   if(g_mqbrowserinfo.isSafari)
   {
      node = mqGetNode(xmlDoc, strPath);
      var nodeText="";
      //for now support for @ only
      if(strPath.indexOf('@') != -1)
      {
         attribute = strPath.substr(strPath.indexOf('@')+1, strPath.length);
         nodeText = node.attributes.getNamedItem(attribute).nodeValue ;
      }
      else if(node)
      {
         nodeText = mqGetNodeText(node) ;
      }
      return nodeText;
   }
   //Safari adaptation stop--
   if (g_mqbrowserinfo.isIE) {
      node = xmlDoc.selectSingleNode(strPath);
      return (node == null ? "" : node.text);
   } else if (g_mqbrowserinfo.isNS) {
      node = xmlDoc.evaluate(strPath, xmlDoc, null, 2, null);
      return node.stringValue;
   }

   return "";
}

// used by mqSetNodeText after it finds the node
// using an XPath expr, so other funcs that have
// a nodeList can set text on each node in a loop
function mqReplaceNode(xmlDoc,node,strTxt) {
   var ndNewText = xmlDoc.createTextNode(strTxt);

   if (node.firstChild) {
      return node.replaceChild(ndNewText,node.firstChild);
   } else {
      return node.appendChild(ndNewText);
   }
}

//used to replace/add text to an existing node
//ie <location><address></address></location>
//if this function is given the xpath /location/address
//it will add text to the address node if not present or replace
//existing text. It will not add an address node.
function mqSetNodeText(xmlDoc,strXPath,strTxt) {
   var ndParent = mqGetNode(xmlDoc,strXPath);

   if (ndParent == null) {
      return null;
   }
   mqReplaceNode(xmlDoc,ndParent,strTxt);
}


//crossbrowser wrapper used to return xhtml from xml xsl strings.
function mqTransformXMLFromString(strXml,strXsl,dvParent) {
   var xmlDoc = mqCreateXMLDoc(strXml);
   var xslDoc = mqCreateXMLDoc(strXsl);
   var newFragment;

   if (g_mqbrowserinfo.isNS) {
      var xsltProcessor = new XSLTProcessor();
      xsltProcessor.importStylesheet(xslDoc);
      newFragment = xsltProcessor.transformToFragment(xmlDoc, document);

      dvParent.appendChild(newFragment);
   } else if (g_mqbrowserinfo.isIE) {
      var newFragment = new ActiveXObject("Msxml2.DOMDocument.5.0");
      newFragment = xmlDoc.transformNode(xslDoc);

      dvParent.innerHTML += newFragment;
   }
}


//crossbrowser wrapper used to return xhtml from an xml node an xsl string.
function mqTransformXMLFromNode(ndXml,strXsl,dvParent) {
   var xslDoc = mqCreateXMLDoc(strXsl);
   var newFragment;

   if (g_mqbrowserinfo.isNS) {
      var xsltProcessor = new XSLTProcessor();
      xsltProcessor.importStylesheet(xslDoc);
      newFragment = xsltProcessor.transformToFragment(ndXml, document);

      dvParent.appendChild(newFragment);
   } else if (g_mqbrowserinfo.isIE) {
      var newFragment = new ActiveXObject("Msxml2.DOMDocument.5.0");
      newFragment = ndXml.transformNode(xslDoc);

      dvParent.innerHTML += newFragment;
   }
}



/*******************************************************************************/
/* Browser  code                                                      */
/*******************************************************************************/
function getBrowserInfo()
{
   var browser             = new Object();
   browser.name            = browser.version = browser.os = "unknown";
   var userAgent           = navigator.userAgent.toLowerCase();
   var browserListArray    = new Array("firefox", "msie", "netscape", "opera", "safari");
   var osListArray         = new Array("linux", "mac", "windows", "x11");
   for(var i = 0, n = browserListArray.length; i < n; i++)
   {   // get browser name and version
      var strPosition = userAgent.indexOf(browserListArray[i]) + 1;
      if(strPosition > 0)
      {
         browser.name = browserListArray[i]; // browser name

         var versionPosition = strPosition + browser.name.length;
         var incr = ((browser.name == "safari") || (userAgent.charAt(versionPosition + 4) > 0 && userAgent.charAt(versionPosition + 4) < 9)) ? 5 : 3;
         browser.version     = userAgent.substring(versionPosition, versionPosition + incr); // browser version
      }
   }
   for(var i = 0, n = osListArray.length; i < n; i++)
   {
      var strPosition = userAgent.indexOf(osListArray[i]) + 1;
      if(strPosition > 0)
      {
         browser.os  = osListArray[i];
      }
   }


   var appname = navigator.appName;
   if (appname == "Netscape")
      browser.appname = "ns";
   else if (appname == "Microsoft Internet Explorer")
      browser.appname = "ie";

   browser.appVersion = navigator.appVersion;
   browser.vMajor  = parseInt(browser.appVersion);
   browser.isNS    = (browser.appname =="ns" && browser.vMajor >= 4);
   browser.isNS4   = (browser.appname =="ns" && browser.vMajor == 4);
   browser.isNS6   = (browser.appname =="ns" && browser.vMajor == 5);
   browser.isIE    = (browser.appname =="ie" && browser.vMajor >= 4);
   browser.isIE4   = (browser.appVersion.indexOf ('MSIE 4')   >0);
   browser.isIE5   = (browser.appVersion.indexOf ('MSIE 5')   >0);
   browser.isDOM   = (document.createElement
                     && document.appendChild
                     && document.getElementsByTagName) ? true : false;
   //safari sniffing --start
   var detect = navigator.appVersion.toLowerCase();
   browser.isSafari = (detect.indexOf('macintosh')+1)?true:false;
   //if mac then assume safari
   //safari sniffing --stop

   var ua = navigator.userAgent.toLowerCase();
   if (ua.indexOf ("win") > - 1)
      browser.platform = "win";
   else if (ua.indexOf("mac") > -1)
      browser.platform = "mac";
   else
      browser.platform="other";

   return browser;

} //getBrowserInfo()

var g_mqbrowserinfo = new getBrowserInfo();

/**
 * =PNG/IE FIX
 * Work around to make PNG alpha transparency for IE - Thanks to Drew McLellan @ allinthehead.com
 */
//window.attachEvent("onload", alphaBackgrounds);
addEvent(window, "load",alphaBackgrounds);
function alphaBackgrounds(){
   if(navigator.platform == "Win32" && navigator.appName == "Microsoft Internet Explorer" && window.attachEvent) {
      var rslt = navigator.appVersion.match(/MSIE (\d+\.\d+)/, '');
      var itsAllGood = (rslt != null && Number(rslt[1]) >= 5.5);
      for (i=0; i<document.all.length; i++){
         var bg = document.all[i].currentStyle.backgroundImage;
         if (itsAllGood && bg){
            if (bg.match(/\.png/i) != null){
               var mypng = bg.substring(5,bg.length-2);
               document.all[i].style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+mypng+"', sizingMethod='scale')";
               document.all[i].style.backgroundImage = "url(/images/background-form-button.gif)";
            }
         }
      }
   }
}

/*******************************************************************************/
/* Following section contains functions for formatting numbers/time/distance   */
/*******************************************************************************/
//function used to format a number (num) to x(dec) decimal places
function formatNumber(num,dec) {
   return Math.floor(num * Math.pow(10,dec))/Math.pow(10,dec);
}

//function displays the time in format x hours, x.xx minutes or x.xx minutes
function mq_display_time(totalTime) {
   var newTime;
   // more than a minute
   if(totalTime > 3600)
   {
      newTime = totalTime/3600;
      var result = (" " + Math.floor(newTime) + " hours,");
      newTime = (totalTime/60)%60;
      result += (" " + formatNumber(newTime,2) + " minutes");
      return result;
   }
   if(totalTime > 60)
   {
      newTime = totalTime/60;
      return (" " + formatNumber(newTime,2) + " minutes");
   }
}

//function used to display distance formated in a div with right alignment.
//outputs miles or kilometers with 2 digits of precision
function mq_display_distance(totalDistance) {
   return (" " + formatNumber(totalDistance.value,2) + (totalDistance.units == "mi" ? " miles" : " kilometers"));
}




/*******************************************************************************/
/* Following section contains functions for adding common DOM elements to tree */
/*******************************************************************************/
var isIE5Mac = (navigator.userAgent.indexOf('MSIE 5') != -1 && navigator.userAgent.indexOf('Mac') != -1);

function createFormInput (container, id, spanClass, labelTxt, type, name, value, size, maxLength) {
    var div = container.appendChild (document.createElement ('div'));
        div.className = 'row';
    var label = div.appendChild (document.createElement ('label'));
        label.htmlFor = id;
        label.appendChild (document.createTextNode (labelTxt));
    div.appendChild (document.createElement ('br'));

    var input = document.createElement ('input');

    input.id        = id;
    input.type      = type;
    input.name      = name;

    if (size != "") {
        input.size      = size;
    }
    if (!isNaN(parseInt(maxLength))) {
        input.maxLength = parseInt(maxLength);
    }
    if (value != "") {
        input.value = value;
    }

    if (spanClass != "") {
        var span = div.appendChild (document.createElement ('span'));
            span.className = spanClass;
            span.appendChild (input);
    } else {
        div.appendChild (input);
    }
}

function createInput (container, id, type, name, value, size, maxLength) {
    var input = document.createElement ('input');
        input.id        = id;
        input.type      = type;
        input.name      = name;

    if (size != "") {
        input.size      = size;
    }
    if (!isNaN(parseInt(maxLength))) {
        input.maxLength = parseInt(maxLength);
    }
    if (value != "") {
        input.value = value;
    }
    container.appendChild (input);
}

function createHiddenInput (form, id, name, value) {
    var input;
    if (isIE5Mac) {
        input = document.createElement ('input type=hidden');
    } else {
        input = document.createElement ('input');
        input.type  = 'hidden';
    }
    input.name  = name;
    if (id != '') {
        input.id    = id;
    }
    if (value != '') {
        input.value = value;
    }
    form.appendChild (input);
}

function createFormSelect (container, id, spanClass, labelTxt, name, elements, node) {
    var div = container.appendChild (document.createElement ('div'));
        div.className = 'row';
    var label = div.appendChild (document.createElement ('label'));
        label.htmlFor = id;
        label.appendChild (document.createTextNode (labelTxt));
    div.appendChild (document.createElement ('br'));

    if (spanClass != "") {
        var span = div.appendChild (document.createElement ('span'));
            span.className = spanClass;
        var select = span.appendChild (document.createElement ('select'));
    } else {
        var select = div.appendChild (document.createElement ('select'));
    }

    select.id = id;
    select.name = name;
    for (x=0; x<elements.length; x++) {
        var option = select.appendChild (document.createElement ('option'));
            eval ("option.value = elements[x]." + node);
            eval ("option.appendChild (document.createTextNode (elements[x]." + node + "))");
    }
    return select;
}

function createDiv (container, className, id) {
    var div = container.appendChild (document.createElement ('div'));

    if (className != "") {
        div.className = className;
    }
    if (id != "") {
        div.id = id;
    }
    return div;
}

function createA (container, href, title) {
    var a = container.appendChild (document.createElement ('a'));
        a.href = href;

    if (title != "") {
        a.title = title;
    }
    return a;
}

function createSpan (container, className, id) {
    var span = container.appendChild (document.createElement ('span'));

    if (className != "") {
        span.className = className;
    }
    if (id != "") {
        span.id = id;
    }
    return span;
}

function createImg (container, src, width, height, id, name, alt) {
    var img = container.appendChild (document.createElement ('img'));

    if (src != "") {
        img.src = src;
    }

    if (!isNaN(parseInt(width))) {
        img.width = parseInt(width);
    }
    if (!isNaN(parseInt(height))) {
        img.height = parseInt(height);
    }
    if (id != "") {
        img.id = id;
    }
    if (name != "") {
        img.name = name;
    }
    if (alt != "") {
        img.alt = alt;
    }
    return img;
}

function createImgDiv (container, src, width, height, id, name, alt) {
    var div = container.appendChild (document.createElement ('div'));
    if (id != "") {
        div.id = id;
    }
    if (!isNaN(parseInt(width))) {
        div.style.width = parseInt(width)+"px";
    }
    if (!isNaN(parseInt(height))) {
        div.style.height = parseInt(height)+"px";
    }

    if (name != "") {
        div.name = name;
    }
    if (alt != "") {
        div.alt = alt;
    }
    return div;
}
/**
 * =HTTP XML REQUEST
 * @makes a XMLHttpRequest standardized for supported browsers
 */
function mqXMLHttpRequest()
{
   var request = null;
   if(window.XMLHttpRequest)
   {   //moz, safari1.2+, opera8
      try
      {
         request = new XMLHttpRequest();
         //request.overrideMimeType('text/xml');
      }
      catch(e)
      {
         request = null;
      }
   }
   else if(window.ActiveXObject)
   {   //ie5.5+
      try
      {
         request = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch(e)
      {
         try
         {
         request = new ActiveXObject("Microsoft.XMLHTTP");
         }
         catch(e)
         {
            request = null;
         }
      }
   }
   return request;
} //mqXMLHttpRequest()




/*******************************************************************************/
/*Event Listener Code                                                          */
/*******************************************************************************/
/**
 * =ADD EVENT
 * @attach event listener
 */
function addEvent(fObj, fEvent, fn)
{
    if(window.opera && g_mqbrowserinfo.version < 8)
    {   // opera has bad dynamic event handling
        //eval("fObj.on" + fEvent + " = fn");
        var r = fObj.attachEvent("on"+fEvent, fn);
        return r;
    }
    else if (fObj.addEventListener)
    {   // moz, w3c
        ((window.opera) && (g_mqbrowserinfo.version >= 8))?fObj.addEventListener(fEvent, fn, false):fObj.addEventListener(fEvent, fn, true);
        return true;
    }
    else if (fObj.attachEvent)
    {   // IE
        var r = fObj.attachEvent("on"+fEvent, fn);
        return r;
    }
    else
    {   //other
        fObj["on" + fEvent] = fn;
    }
}//addEvent()

/**
 * =REMOVE EVENT
 * @detach event listener
 */
function removeEvent(fObj, fEvent, fn)
{
    if(window.opera)
    {   // opera has bad dynamic event handling
        eval("fObj.on" + fEvent + " = null");
    }
    if(fObj.removeEventListener)
    {   //w3c
        ((window.opera) && (g_mqbrowserinfo.version >= 8))?fObj.removeEventListener(fEvent, fn, false):fObj.removeEventListener(fEvent, fn, true);
    }
    else if(fObj.detachEvent)
    {   //ie
        fObj.detachEvent("on" + fEvent, fn);
    }
    else
    {   //opera and other
        fObj["on" + fEvent] = null;
    }
} //removeEvent()

/**
 * =GET EVENT DATA
 * @return the id that event is attached to
 */
function getEventData(evt)
{
    fEventData = new Object();
    if(document.addEventListener)
    {
        fEventData.id   = evt.target.id;
        fEventData.type = evt.type;
        fEventData.element = evt.target;
    }
    else if(window.event)
    {
        fEventData.id   = window.event.srcElement.id;
        fEventData.type = window.event.type;
        fEventData.element = window.event.srcElement;
    }
    else
    {
        return null;
    }
    return fEventData;
} //getEventData()


/*******************************************************************************/
/*Document Coordinate calculations                                             */
/*******************************************************************************/
/**
 * GET XY
 * @get the XY coordinates
 * @returns an array containing the event target id, and xy data for page and target
 *
 */
function getXY(evt)
{
    xyData = new Object();
    if(!document.createElement || !document.getElementsByTagName) return;
    if(!document.createElementNS)
    {   // to work in html and xml namespaces
        document.createElementNS = function(ns,elt)
        {
            return document.createElement(elt);
        }
    }
    if(document.addEventListener && typeof evt.pageX == "number")
    {   // Moz and Opera
        var Element                     = evt.target;
        var CalculatedTotalOffsetLeft   = CalculatedTotalOffsetTop = 0;
        while(Element.offsetParent)
        {
            CalculatedTotalOffsetLeft   += Element.offsetLeft;
            CalculatedTotalOffsetTop    += Element.offsetTop;
            Element                      = Element.offsetParent;
        }
        var OffsetXForNS6   = evt.pageX - CalculatedTotalOffsetLeft;
        var OffsetYForNS6   = evt.pageY - CalculatedTotalOffsetTop;
        xyData.elementId    = evt.target.id;
        xyData.elementX     = OffsetXForNS6;
        xyData.elementY     = OffsetYForNS6;
        xyData.pageX        = evt.pageX;
        xyData.pageY        = evt.pageY;
    }
    else if(window.event && typeof window.event.offsetX == "number")
    {   //ie
        xyData.elementId    = window.event.srcElement.id;
        xyData.elementX     = event.offsetX;
        xyData.elementY     = event.offsetY;
        xyData.pageX        = 0;
        xyData.pageY        = 0;
        var element         = getElementById(xyData.elementId);
        while(element)
        {
            xyData.pageX += element.offsetLeft;
            xyData.pageY += element.offsetTop;
            element = element.offsetParent;
        }
        xyData.pageX += xyData.elementX;
        xyData.pageY += xyData.elementY;
    }
    return xyData;
}//getXY()

/**
 * =GET parentDiv SIZE
 * @get height and width of containing div canvas
 */
function getPDivSize( pMQMapObject )
{
    // for openapi
    // 2 pixels padding overall div
    size = new MQSize();

    // Temporary defaults if the user hasn't set div size
    if( pMQMapObject.parent.style.width.length == 0)
      pMQMapObject.parent.style.width = "800px";
    if( pMQMapObject.parent.style.height.length == 0)
      pMQMapObject.parent.style.height = "600px";

    size.setWidth(parseInt(pMQMapObject.parent.style.width) - 4);
    size.setHeight(parseInt(pMQMapObject.parent.style.height) - 4);
    return size;
}

/**
 * =SET parentDiv SIZE
 * @set height and width of containing div canvas
 */
function setPDivSize( pMQMapObject, size )
{
    pMQMapObject.parent.style.width  = size.getWidth() + "px";
    pMQMapObject.parent.style.height = size.getHeight() + "px";
}

/**
*  urlencode used to fix url before server call
*/
function mqurlencode(strVal)
{
   var strEncode;
   strEncode = strVal.replace(/%/g,"%25");
   strEncode = strEncode.replace(/&/g,"%26");
   strEncode = strEncode.replace(/#/g,"%23");
   strEncode = strEncode.replace(/\//g,"%2F");
   strEncode = strEncode.replace(/:/g,"%3A");
   strEncode = strEncode.replace(/;/g,"%3B");
   strEncode = strEncode.replace(/=/g,"%3D");
   strEncode = strEncode.replace(/\?/g,"%3F");
   strEncode = strEncode.replace(/@/g,"%40");
   strEncode = strEncode.replace(/\$/g,"%24");
   strEncode = strEncode.replace(/,/g,"%2C");
   strEncode = strEncode.replace(/\+/g,"%2B");
   return strEncode;
}

function getguid()
{
   var org = new Date(2006,0,1);
   var now = new Date();
   do {

      var cur = new Date();
   }

   while(cur - now < 1);
   var diff = cur.getTime()-org.getTime();

   return (Math.ceil(diff));
}

function pause(numberMillis)
{
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true)
    {
        now = new Date();
        if (now.getTime() > exitTime)
            return;
    }
}


function logmsg(str)
{
   if(document.getElementById("logtext"))
   {
      var logtext = document.getElementById("logtext");
      logtext.value = logtext.value+ '\n' + str ;
   }

}

function getAdvantageResultPath(transaction) {
   var resultsPath ;
   if (transaction == "poiMap")
      resultsPath = "poiResults";
   else if (transaction == "locMap")
      resultsPath = "locations";
   else if (transaction == "search")
      resultsPath = "searchResults";

   return resultsPath;
}
function getAdvantageMapPath(transaction) {
   var mapPath ;
   if (transaction == "locMap")
      mapPath = "/advantage/"+transaction+"/locations/location/map";
   else
      mapPath = "/advantage/"+transaction+"/map";
   return mapPath;
}
