
try{var testCommons=new MQObject();testCommons=null;}catch(error){throw"You must include mqcommon.js or toolkit api script prior to mqexec.js.";}
function MQExec(strServerNameORmqeObj,strPathToServer,nServerPort,strProxyServerName,strPathToProxyServerPage,nProxyServerPort)
{var m_strServerName;var m_strServerPath;var m_nServerPort;var m_strProxyServerPath;var m_strProxyServerName;var m_nProxyServerPort;var m_lSocketTimeout;var m_strXInfo="";if(typeof strServerNameORmqeObj=="string"){m_strServerName=strServerNameORmqeObj||"localhost";m_strServerPath=strPathToServer||"mq";m_nServerPort=nServerPort||80;m_strProxyServerPath=strPathToProxyServerPage||"";m_strProxyServerName=strProxyServerName||"";m_nProxyServerPort=nProxyServerPort||0;m_lSocketTimeout=0;}else if(strServerNameORmqeObj.getClassName()&&strServerNameORmqeObj.getClassName()=="MQExec"){m_strServerName=strServerNameORmqeObj.getServerName();m_strServerPath=strServerNameORmqeObj.getServerPath();m_nServerPort=strServerNameORmqeObj.getServerPort();m_strProxyServerName=strServerNameORmqeObj.getProxyServerName();m_nProxyServerPort=strServerNameORmqeObj.getProxyServerPort();m_strProxyServerPath=strServerNameORmqeObj.getProxyServerPath();m_lSocketTimeout=strServerNameORmqeObj.m_lSocketTimeout;}
this.setServerName=function(strServerName){m_strServerName=strServerName;};this.getServerName=function(){return m_strServerName;};this.setServerPath=function(strServerPath){m_strServerPath=strServerPath;};this.getServerPath=function(){return m_strServerPath;};this.setServerPort=function(nServerPort){m_nServerPort=nServerPort;};this.getServerPort=function(){return m_nServerPort;};this.setProxyServerName=function(strProxyServerName){m_strProxyServerName=strProxyServerName;};this.getProxyServerName=function(){return m_strProxyServerName;};this.setProxyServerPath=function(strProxyServerPath){m_strProxyServerPath=strProxyServerPath;};this.getProxyServerPath=function(){return m_strProxyServerPath;};this.setProxyServerPort=function(nProxyServerPort){m_nProxyServerPort=nProxyServerPort;};this.getProxyServerPort=function(){return m_nProxyServerPort;};this.setTransactionInfo=function(strXInfo){if(strXInfo.length>32)
m_strXInfo=strXInfo.substring(0,32);else
m_strXInfo=strXInfo;};this.getTransactionInfo=function(){return m_strXInfo;};}
MQExec.prototype.ROUTE_VERSION="2";MQExec.prototype.SEARCH_VERSION="0";MQExec.prototype.GEOCODE_VERSION="1";MQExec.prototype.ROUTEMATRIX_VERSION="0";MQExec.prototype.GETRECORDINFO_VERSION="0";MQExec.prototype.REVERSEGEOCODE_VERSION="0";MQExec.prototype.GETSESSION_VERSION="1";MQExec.prototype.getRequestXml=function(strTransaction,arrRequest,strVersion){var arrXmlBuf=new Array();var version=strVersion||"0";arrXmlBuf.push("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n");arrXmlBuf.push("<"+strTransaction+" Version=\""+version+"\">\n");for(var i=0;i<arrRequest.length;i++){arrXmlBuf.push(arrRequest[i].saveXml());arrXmlBuf.push("\n");}
arrXmlBuf.push("</"+strTransaction+">");return arrXmlBuf.join("");};MQExec.prototype.doTransaction=function(strTransaction,arrRequest,strVersion){var xmlDoc;var strResXml;var http_request=mqXMLHttpRequest();var strUrl="";arrRequest.push(new MQAuthentication(this.getTransactionInfo()));var strReqXml=this.getRequestXml(strTransaction,arrRequest,strVersion);if(this.getProxyServerName()!=""){strUrl+="http://"+this.getProxyServerName();if(this.getProxyServerPort()!=0){strUrl+=":"+this.getProxyServerPort();}
strUrl+="/";}
strUrl+=this.getProxyServerPath();strUrl+="?sname="+this.getServerName();strUrl+="&spath="+this.getServerPath();strUrl+="&sport="+this.getServerPort();display("mqXmlLogs","Request URL: ",strUrl,"rURL","mqDisplay");display("mqXmlLogs","Request XML: ",strReqXml,"","mqDisplay");http_request.open("POST",strUrl,false);http_request.send(strReqXml);if(http_request.status==200){xmlDoc=http_request.responseXML;}
else{alert("HTTP Status: "+http_request.status+" ("+http_request.statusText+")\n"+"Details: \n"+http_request.responseText);xmlDoc=null;}
display("mqXmlLogs","Response XML: ",mqXmlToStr(xmlDoc),"resXML","mqDisplay");return xmlDoc;};MQExec.prototype.geocode=function(mqaAddress,mqlcLocations,theOptions){var xmlDoc;var strXml;var arrRequest=new Array();if(mqaAddress==null||(mqaAddress.getClassName()!=="MQAddress"&&mqaAddress.getClassName()!=="MQSingleLineAddress")){throw"Null or Illegal Argument passed for MQAddress";}else{arrRequest.push(mqaAddress);}
if(mqlcLocations==null||mqlcLocations.getClassName()!=="MQLocationCollection"){throw"Null or Illegal Argument passed for MQLocationCollection";}
if(theOptions!=null){if(theOptions.getClassName()!=="MQAutoGeocodeCovSwitch"&&theOptions.getClassName()!=="MQGeocodeOptionsCollection"){throw"Illegal Argument passed for Geocode Options";}else{arrRequest.push(theOptions);}}
mqLogTime("MQExec.geocode: Transaction Start");xmlDoc=this.doTransaction("Geocode",arrRequest,this.GEOCODE_VERSION);mqLogTime("MQExec.geocode: Transaction End");mqLogTime("MQExec.geocode: Loading of GeocodeResponse Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/GeocodeResponse/LocationCollection"));mqlcLocations.loadXml(strXml);mqLogTime("MQExec.geocode: Loading of GeocodeResponse End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.batchGeocode=function(mqlcLocations,mqlccLocations,theOptions){var xmlDoc;var strXml;var arrRequest=new Array();if(mqlcLocations==null||mqlcLocations.getClassName()!=="MQLocationCollection"){throw"Null or Illegal Argument passed for MQLocationCollection";}else{arrRequest.push(mqlcLocations);}
if(mqlccLocations==null||mqlccLocations.getClassName()!=="MQLocationCollectionCollection"){throw"Null or Illegal Argument passed for MQLocationCollectionCollection";}
if(theOptions!=null){if(theOptions.getClassName()!=="MQAutoGeocodeCovSwitch"&&theOptions.getClassName()!=="MQGeocodeOptionsCollection"){throw"Illegal Argument passed for Geocode Options";}else{arrRequest.push(theOptions);}}
mqLogTime("MQExec.batchGeocode: Transaction Start");xmlDoc=this.doTransaction("BatchGeocode",arrRequest,this.GEOCODE_VERSION);mqLogTime("MQExec.batchGeocode: Transaction End");mqLogTime("MQExec.batchGeocode: Loading of GeocodeResponse Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/BatchGeocodeResponse/LocationCollectionCollection"));mqlccLocations.loadXml(strXml);mqLogTime("MQExec.batchGeocode: Loading of GeocodeResponse End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.doRoute=function(mqlcLocations,mqroOptions,mqrrResults,strSessionUID,mqRectLL){var xmlDoc;var strXml;var arrRequest=new Array();if(mqlcLocations==null||mqlcLocations.getClassName()!=="MQLocationCollection"){throw"Null or Illegal Argument passed for MQLocationCollection";}else{arrRequest.push(mqlcLocations);}
if(mqroOptions==null||mqroOptions.getClassName()!=="MQRouteOptions"){throw"Null or Illegal Argument passed for MQRouteOptions";}else{arrRequest.push(mqroOptions);}
if(mqrrResults==null||mqrrResults.getClassName()!=="MQRouteResults"){throw"Null or Illegal Argument passed for MQRouteResults";}else{var sessionId=strSessionUID||"";arrRequest.push(new MQXmlNodeObject("SessionID",sessionId));}
mqLogTime("MQExec.doRoute: Transaction Start");xmlDoc=this.doTransaction("DoRoute",arrRequest,this.ROUTE_VERSION);mqLogTime("MQExec.doRoute: Transaction End");mqLogTime("MQExec.doRoute: Loading of RouteResults Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/DoRouteResponse/RouteResults"));mqrrResults.loadXml(strXml);mqLogTime("MQExec.doRoute: Loading of RouteResults End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");if(mqRectLL!==null&&sessionId!==""){this.getRouteBoundingBoxFromSessionResponse(sessionId,mqRectLL);}};MQExec.prototype.createSessionEx=function(mqsSession){var xmlDoc;var strSessId;var arrRequest=new Array();if(mqsSession==null||mqsSession.getClassName()!=="MQSession"){throw"Null or Illegal Argument passed for MQSession";}else{arrRequest.push(mqsSession);}
xmlDoc=this.doTransaction("CreateSession",arrRequest);strSessId=mqGetNodeText(mqGetNode(xmlDoc,"/CreateSessionResponse/SessionID"));return strSessId;};MQExec.prototype.getSession=function(strSessionID,mqObj){var xmlDoc;var strXml;var sessionId=strSessionID||"";var arrRequest=new Array();arrRequest.push(new MQXmlNodeObject("SessionID",sessionId));xmlDoc=this.doTransaction("GetSession",arrRequest,this.GETSESSION_VERSION);if(mqObj.getClassName()==="MQMapState"){strXml=mqXmlToStr(mqGetNode(xmlDoc,"/GetSessionResponse/Session/MapState"));mqObj.loadXml(strXml);}else if(mqObj.getClassName()==="MQSession"){strXml=mqXmlToStr(mqGetNode(xmlDoc,"/GetSessionResponse/Session"));mqObj.loadXml(strXml);}};MQExec.prototype.doRouteMatrix=function(mqlcLocations,bAllToAll,mqroOptions,mqrmrResults){var xmlDoc;var strXml;var arrRequest=new Array();if(mqlcLocations==null||mqlcLocations.getClassName()!=="MQLocationCollection"){throw"Null or Illegal Argument passed for MQLocationCollection";}else{arrRequest.push(mqlcLocations);}
if(bAllToAll==null||typeof bAllToAll!="boolean"){throw"Null or Illegal Argument passed for bAllToAll";}else{var iAllToAll=bAllToAll?1:0;arrRequest.push(new MQXmlNodeObject("AllToAll",iAllToAll));}
if(mqroOptions==null||mqroOptions.getClassName()!=="MQRouteOptions"){throw"Null or Illegal Argument passed for MQRouteOptions";}else{arrRequest.push(mqroOptions);}
if(mqrmrResults==null||mqrmrResults.getClassName()!=="MQRouteMatrixResults"){throw"Null or Illegal Argument passed for MQRouteMatrixResults";}
mqLogTime("MQExec.doRoute: Transaction Start");xmlDoc=this.doTransaction("DoRouteMatrix",arrRequest,this.ROUTEMATRIX_VERSION);mqLogTime("MQExec.doRoute: Transaction End");mqLogTime("MQExec.doRoute: Loading of RouteResults Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/DoRouteMatrixResponse/RouteMatrixResults"));mqrmrResults.loadXml(strXml);mqLogTime("MQExec.doRoute: Loading of RouteResults End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.getRecordInfo=function(mqscFieldNames,mqdlqQuery,mqrsResults,mqscRecIds){var xmlDoc;var strXml;var arrRequest=new Array();if(mqscFieldNames==null||mqscFieldNames.getClassName()!=="MQStringCollection"){throw"Null or Illegal Argument passed for MQStringCollection";}else{var fields=new MQStringCollection();fields.setM_Xpath("Fields");fields.append(mqscFieldNames);arrRequest.push(fields);}
if(mqdlqQuery==null||mqdlqQuery.getClassName()!=="MQDBLayerQuery"){throw"Null or Illegal Argument passed for MQDBLayerQuery";}else{arrRequest.push(mqdlqQuery);}
if(mqrsResults==null||mqrsResults.getClassName()!=="MQRecordSet"){throw"Null or Illegal Argument passed for MQRecordSet";}
if(mqscRecIds==null||mqscRecIds.getClassName()!=="MQStringCollection"){throw"Null or Illegal Argument passed for MQStringCollection";}else{var recordIds=new MQStringCollection();recordIds.setM_Xpath("RecordIds");recordIds.append(mqscRecIds);arrRequest.push(recordIds);}
mqLogTime("MQExec.getRecordInfo: Transaction Start");xmlDoc=this.doTransaction("GetRecordInfo",arrRequest,this.GETRECORDINFO_VERSION);mqLogTime("MQExec.getRecordInfo: Transaction End");mqLogTime("MQExec.getRecordInfo: Loading of RecordSet Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/GetRecordInfoResponse/RecordSet"));mqrsResults.loadXml(strXml);mqLogTime("MQExec.getRecordInfo: Loading of RecordSet End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.reverseGeocode=function(mqllLatLng,mqlcLocations,strMapCovName,strGeocodeCovName){var xmlDoc;var strXml;var arrRequest=new Array();if(mqllLatLng==null||mqllLatLng.getClassName()!=="MQLatLng"){throw"Null or Illegal Argument passed for MQLatLng";}else{arrRequest.push(mqllLatLng);}
if(mqlcLocations==null||mqlcLocations.getClassName()!=="MQLocationCollection"){throw"Null or Illegal Argument passed for MQLocationCollection";}
var mapPool=strMapCovName||"";arrRequest.push(new MQXmlNodeObject("MapPool",mapPool));var geocodePool=strGeocodeCovName||"";arrRequest.push(new MQXmlNodeObject("GeocodePool",geocodePool));mqLogTime("MQExec.reverseGeocode: Transaction Start");xmlDoc=this.doTransaction("ReverseGeocode",arrRequest,this.REVERSEGEOCODE_VERSION);mqLogTime("MQExec.reverseGeocode: Transaction End");mqLogTime("MQExec.reverseGeocode: Loading of Response Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/ReverseGeocodeResponse/LocationCollection"));mqlcLocations.loadXml(strXml);mqLogTime("MQExec.reverseGeocode: Loading of Response End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.search=function(mqscCriteria,mqfcSearchResults,strCoverageName,mqdlqcDbLayers,mqfcFeatures,mqdcDisplayTypes){var xmlDoc;var strXml;var arrRequest=new Array();var strName=mqscCriteria?mqscCriteria.getClassName():null;if(strName==null||(strName!=="MQSearchCriteria"&&strName!=="MQRadiusSearchCriteria"&&strName!=="MQRectSearchCriteria"&&strName!=="MQPolySearchCriteria"&&strName!=="MQCorridorSearchCriteria")){throw"Null or Illegal Argument passed for Search Criteria";}else{arrRequest.push(mqscCriteria);}
if(mqfcSearchResults==null||mqfcSearchResults.getClassName()!=="MQFeatureCollection"){throw"Null or Illegal Argument passed for MQFeatureCollection";}
if(typeof strCoverageName!=="string"){throw"Illegal Argument passed for strCoverageName";}else{arrRequest.push(new MQXmlNodeObject("CoverageName",strCoverageName));}
if(mqdlqcDbLayers!=null&&mqdlqcDbLayers.getClassName()!=="MQDBLayerQueryCollection"){throw"Illegal Argument passed for MQRouteOptions";}else if(mqdlqcDbLayers==null){mqdlqcDbLayers=new MQDBLayerQueryCollection();}
arrRequest.push(mqdlqcDbLayers);if(mqfcFeatures!=null&&mqfcFeatures.getClassName()!=="MQFeatureCollection"){throw"Illegal Argument passed for MQFeatureCollection";}else if(mqfcFeatures==null){mqfcFeatures=new MQFeatureCollection();}
arrRequest.push(mqfcFeatures);if(mqdcDisplayTypes!=null&&mqdcDisplayTypes.getClassName()!=="MQDTCollection"){throw"Illegal Argument passed for MQDTCollection";}else if(mqdcDisplayTypes==null){mqdcDisplayTypes=new MQDTCollection();}
arrRequest.push(mqdcDisplayTypes);mqLogTime("MQExec.Search: Transaction Start");xmlDoc=this.doTransaction("Search",arrRequest,this.SEARCH_VERSION);mqLogTime("MQExec.Search: Transaction End");mqLogTime("MQExec.Search: Loading of Search results Start");strXml=mqXmlToStr(mqGetNode(xmlDoc,"/SearchResponse/FeatureCollection"));mqfcSearchResults.loadXml(strXml);mqLogTime("MQExec.Search: Loading of Search results End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");};MQExec.prototype.getRouteBoundingBoxFromSessionResponse=function(sessionId,mqRectLL){var xmlDoc;var strXml;var arrRequest=new Array();if(mqRectLL==null){throw"Null or Illegal Argument passed for MQRectLL";}
arrRequest.push(new MQXmlNodeObject("SessionID",sessionId));xmlDoc=this.doTransaction("GetRouteBoundingBoxFromSession",arrRequest);mqLogTime("MQExec.doRoute: Loading of MQRectLL Start");var nodes=xmlDoc.documentElement.childNodes;var ul=new MQLatLng();ul.loadXml(mqXmlToStr(nodes[0]));var lr=new MQLatLng();lr.loadXml(mqXmlToStr(nodes[1]));mqRectLL.setUpperLeft(ul);mqRectLL.setLowerRight(lr);mqLogTime("MQExec.doRoute: Loading of MQRectLL End");};MQExec.prototype.isAlive=function(){if(this.getServerPort()==-1||this.getServerName()=="")
return false;return true;};MQExec.prototype.getServerInfo=function(lType){if(!this.isAlive())
return null;var strReqXml;var xmlDoc;var strXml;var type=lType||0;var arrRequest=new Array();if(typeof type!=="number"){throw"Illegal Argument passed for lType";}else{arrRequest.push(new MQXmlNodeObject("Type",type));}
mqLogTime("MQExec.GetServerInfo: Transaction Start");xmlDoc=this.doTransaction("GetServerInfo",arrRequest);mqLogTime("MQExec.GetServerInfo: Transaction End");display("results","Response",mqXmlToStr(xmlDoc),"","mqDisplay");return xmlDoc;};