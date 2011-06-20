<?php

// Page layout functions

function nicetime($seconds)
{
	if($seconds < 60)
		return round($seconds)." seconds";
	elseif($seconds < 60*60)
		return round($seconds/60)." minutes";
	elseif($seconds < 60*60*24)
		return round($seconds/(60*60))." hours";
	elseif($seconds < 60*60*24*30)
		return round($seconds/(60*60*24))." days";
	else
		return round($seconds/(60*60*24*30))." months";
}
function page_exists() {
	global $page_array, $special;
	return !$page_array["isspecial"] && $page_array["isreal"];
}
function page_lastupdate() {
	global $page_array;
	return $page_array["lastupdate"] ? (nicetime(time() - $page_array["lastupdate"])." ago") : "";
}
function def($a, $b) { return $a ? $a : $b; }

$page_link = $pages_dir . $page_array["slug"];
$iseditting = ($action == "edit" && login_is());
$page_title = def($page_array["title"], $page_array["slug"]);

?>
<html>
<head>
	<title><?php echo $page_title; ?> - JakeMadeThis</title>
	<link rel="stylesheet" type="text/css" href="/p/base.css" />
	<script type="text/javascript" src="/p/scripts.js"></script>
<?php if($iseditting || $action == "view" || $action == "delete" || $action == "rename"): ?>
	<script type="text/javascript">
	onload = function(){ if(edit=document.getElementById("editform"))window.scrollTo(0,edit.offsetTop); }
	</script>
<?php endif; /* view source */ ?>
</head>
<body>
<div id="container">
	<div id="top">
		<h1><a href="<?php echo $page_link; ?>"><?php echo $page_title; ?></a></h1>
	</div>
	<div id="main">
		<div id="page">
<?php if(count($messages) > 0) echo "<div id=\"messages\">".implode("<br>\n", $messages)."</div>"; ?>
<?php if($page_array["isspecial"] || $page_array["isreal"]): ?>
			<?php echo $page_array["html"]; ?>
<?php elseif(!$page_array["childs"]): ?>
			This page does not exist. <a href="<?php echo $page_link; ?>?edit" rel="nofollow">Create this page?</a>
<?php endif; ?>

<?php if($page_array["childs"]): ?>
			<ul id="childlist">
<?php foreach($page_array["childs"] as &$child): ?>
				<li>
					<a href="/p/<?php echo $child["slug"]; ?>">
						<h2><?php echo $child["title"]; ?></h2>
<?php if($child["excerpt"]): ?>
						<p><?php echo $child["excerpt"]; ?></p>
<?php endif; ?>
					</a>
				</li>
<?php endforeach; ?>
			</ul>
<?php endif; ?>
			
			<div id="footer">
				jakemadethis.
<?php if($page_array["lastupdate"]): ?>
				This page last was updated <?php echo page_lastupdate(); ?>.
<?php endif; ?>
				<a href="<?php echo $page_link; ?>?edit" rel="nofollow">edit</a>
			</div>
			
<?php if($iseditting || $action == "view" || $action == "delete" || $action == "rename") require("editforms.php"); ?>
		</div>
	</div>
	<div id="side">
		<ul id="menu">
			<li><a href="/p/home">jakemadethis</a></li>
			<li><a href="/p/projects">Projects</a></li>
			<li><a href="/p/experimental">Experiments</a></li>
			<li><a href="/p/game">Games</a></li>
			<li><a href="/p/notes">Notes</a></li>
		</ul>
		
<?php if($page_array["tags"]): ?>
		<ul id="menu2">
<?php foreach($page_array["tags"] as &$tag): ?>
			<li><a href="<?php echo $tag; ?>"><?php echo $tag; ?></a></li>
<?php endforeach;  ?>
		</ul>
<?php endif; ?>

	</div>
</div>
</body>

</html>
