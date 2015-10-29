var Reea = Reea || {};
Reea.debug = true;

$j = jQuery.noConflict();

var console = console || {};
console.log = console.log || function () {return false;};

if (!Reea.debug) {
	console.log = function () {return false;};
}
