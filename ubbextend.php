<?php
/* ubbPagesParser */
class ubbPagesParser extends ubbParser
{
	var $pagename;
	var $swfs;

	function valid_url($href)
	{
		 $lowhref = strtolower($href);
		 return (substr($lowhref,0,7)=='http://' || substr($lowhref,0,6)=='ftp://' || substr($lowhref,0,7)=='mailto:' || substr($lowhref,0,1) == '/');
	}
	function page_url($href, &$matches)
	{
		return preg_match("/^(?:([a-z]+):)?([a-z0-9\-\/]*)$/", $href, $matches);
		if(count($matches) < 1) return false;
		return $matches;
	}
	function parse_url($tree, $params = array())
	{
		global $pages_dir;
		
		/* [url]href[/url] as well as [url=href]text[/url] is supported */
		$href = isset($params['url']) ? $params['url'] : $tree->toText();
		if(!$this->valid_url($href) && $this->page_url($href, $m))
		{
			if($m[2] == 'self')
				$href = (strlen($m[1]) > 0 ? $m[1].':' : '').$this->pagename;
			$href = "$pages_dir$href";
		}

		return $this->simple_parse($tree, '<a href="'.htmlspecialchars($href).'">', '</a>');
	}
	function parse_code($tree) {
		return '<blockquote><b class="head">Code:</b><pre>'.htmlspecialchars($tree->toText()).'</pre></blockquote>';
	}
	function parse_quote($tree, $params = array())
	{
		$quotehead = isset($params['quote']) ? '<b class="head">'.$params['quote'].':</b>' : '';

		return $this->simple_parse($tree, "<blockquote>$quotehead", '</blockquote>');
	}
	function parse_comment($tree) {
		return "";
	}
	function parse_noparse($tree) {
		return $this->simple_parse($tree, '', '', false);
	}
	
	function parse_swf($tree, $params = array()) {
		$size = explode("x", isset($params['size']) ? $params['size'] : '740x480');
		$name = $tree->toText();
		$href = isset($params['swf']) ? $params['swf'] : $name;

		list($w, $h) = $size;

		// Can't be too sure
		$w = htmlspecialchars($w);
		$h = htmlspecialchars($h);

		$name = htmlspecialchars($name);
		$href = htmlspecialchars($href);
		/*if(!$this->swfs) $this->swfs = array();

		$count = array_push($this->swfs, array(
			"swf" => $href,
			"width" => $size[0],
			"height" => $size[1]));
		$id = $count-1;
		return $this->simple_parse($tree, "<p id=\"swf$id\" class=\"swf\">", '</p>');*/
		return <<<HTML
<div class="swf" style="width:{$w}px; height:{$h}px">
<div class="collapse">
<object width="$w" height="$h">
<param name="movie" value="$href"></param>
<param name="allowFullScreen" value="true"></param>
<param name="allowscriptaccess" value="always"></param>
<embed src="$href" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="$w" height="$h"></embed>
</object>
</div>
<a href="$href" onclick="showSWF(this); return false">Show $name</a>
</div>
HTML;
	}
}
?>
