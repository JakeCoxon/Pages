function showSWF(link)
{
	var c = link.parentNode.childNodes;
	for(var i=0; i<c.length; i++)
	{
		if(c[i].className && c[i].className == "collapse")
			c[i].style.display = "block";
	}
	link.style.display = "none";
}
function toggleclass(id, newclass)
{
	var el = typeof(id) == "string" ? document.getElementById(id) : id;
	if(!el) return false;
	var classes = el.className.split(" ");
	for(var i=0; i<classes.length; i++) {
		if(classes[i] == newclass) {
			classes.splice(i, 1);
			el.className = classes.join(" ");
			return false;
		}
	}
	classes.push(newclass);
	el.className = classes.join(" ");
	return true;
}
function scrollmore(id)
{
	window.setTimeout(function() {
		var el = typeof(id) == "string" ? document.getElementById(id) : id;
		if(el)
			window.scrollBy(0, el.clientHeight);
	}, 5);
}
