function promtcategoryDelete(url,useAjax, categoryId)
{
  var answer = prompt("Please provide the password to delete this category.");

	if(answer == 'apmr1657'){
		tree.nodeForDelete = categoryId;
		updateContent(url, {}, true);
	}else{
		alert('The password is not correct.');
	}
}

var canvasWidth = window.screen.availWidth-50;
var canvasHeight = window.screen.availHeight-150;

function setZoom (){
    
    var imgWidth = jQuery("#fullResImage").width();
    var imgHeight = jQuery("#fullResImage").height();
    
    if(imgWidth>canvasWidth){
        imgWidth=canvasWidth;
    }
    
    if(imgHeight>canvasHeight){
        imgHeight=canvasHeight;
    }
    
    jQuery('#fullResImage').smoothZoom('destroy').smoothZoom({width:imgWidth,height:imgHeight,zoom_SINGLE_STEP: false});
}
        
function closeZoom (){
    jQuery('#fullResImage').smoothZoom('destroy');
}
