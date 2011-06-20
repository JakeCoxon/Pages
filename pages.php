<?php
require("../php/jdb.inc.php");
// See: http://jakemadethis.com/p/database-class

require("../php/login.php");
// This file needs one function 'login_is()', returns true if logged in.

require("../php/config.php");
// Global variables: $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME

require("./quickerubb.php");
require("./ubbextend.php");


/*

pages
-----

slug   varchar(16)
title  varchar(32)
body   varchar(1024)
excerpt   varchar(1024)
lastupdate   int

pages_tags
-----------

id		smallint
slug	varchar(16)
tag		varchar(16)

*/

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function array_place($str, $arr) {
	return array_map(create_function('$k,$v', 'return '.$str.';'), 
					array_keys($arr), array_values($arr));
}
function redirect($url) {
	header("Location: $url");
	exit();
}

// Time how long it takes to run script
$start_time = microtime_float();

// Start dir
$pages_dir = "/p/";

// Merge $_GET and $_POST, latter has precedence.
$request = array_merge($_GET, $_POST);

// Page is requested page or default to home
$page = $request["page"] or $page = "home";

// Set $action to one of edit, view, rename, delete, if exists in query string
$action = reset(array_intersect(array_keys($request), explode(",", "edit,view,rename,delete")));


// Connect to main database
$db = new Database($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, true);


// Set up base array. Fill out later for current page.
$page_array = array(
	"isspecial" => false,	// If the page is special
	"isreal" => false,		// If the page is real, ie exists in database
	"slug" => $page,		// Slug for template use
	"title" => "",			// Title
	"body" => "",			// Raw page body
	"html" => "",			// Generated HTML
	"excerpt" => "",		// Short body
	"lastupdate" => 0,		// UTS of last update
	"tags" => array(),		// array of tags as array("tag1","tag2","tag3")
	"childs" => array()		// array of children as arrays of arrays like $page_array
);

$message = array();

/// Special Pages ///////////////////


if($page == "all")
{
	// Get all pages and merge special data with page array
	$page_array = array_merge($page_array, array(
		"isspecial" => true,
		"title" => "All pages",
		"html" => "",
		"childs" => $db->select_rows("SELECT slug,title,excerpt FROM pages ORDER BY title ASC")
	));
}


/// Regular Pages ///////////////////

if(!$page_array["isspecial"])
{
	$page_escape = mysql_real_escape_string($page);
	
	// Merge with $page_array
	if($newarray = $db->select_row("SELECT * FROM pages WHERE slug='$page_escape' LIMIT 1"))
		$page_array = array_merge($page_array, $newarray);
	
	// Page if database result carries a lastupdate variable
	$page_array["isreal"] = ($page_array["lastupdate"] != null);
	
	// Pages can exist without being in the database,
	// If they exist in database:
	if($page_array["isreal"])
	{
		// SQL returns the tags as array(array("tag" => "page1"), array("tag" => "page2"));
		// So place each value of "tag" into a new array
		$page_array["tags"] = array_place('$v[tag]',
			$db->select_rows("SELECT tag FROM pages_tags WHERE slug='$page_escape' ORDER BY tag")
		);
	}
	
	// Get all child pages
	$page_array["childs"] = $db->select_rows(
		"SELECT pages.slug, pages.title, pages.excerpt
		 FROM pages, pages_tags
		 WHERE pages.slug = pages_tags.slug AND pages_tags.tag = '$page_escape'
		 ORDER BY pages.title");
	
	/// Actions to perform on page ///////////////////
	
	
	// ACTION : view
	if($action == "view") {
		// This lets anyone view the source of the page.
		// Handle that in the output PHP, but do nothing here
	}
	
	// Everything else should require a password (Use elseif under here)
	elseif($action != "" && !login_is()) {
		$messages[] = "You're not <a href=\"/php/login.php?redirect=$pages_dir$page%3Fedit\" rel=\"nofollow\">logged in</a>, silly";
	}
	
	// ACTION : delete
	elseif($action == "delete") {
		
		// We can delete pages. A confirmation button is needed to make it
		// harder to accidently delete pages
		
		// Error and skip down is the page doesn't even exist
		if(!$page_array["isreal"]) {
			$messages[] = "This page doesn't exist in the database";
		}
		
		// Only continue if confirmed
		elseif(isset($_POST["confirm"])) {
			
			// Do the page deletion
			$result = $db->query("DELETE FROM pages WHERE slug='$page_escape'");
			
			if(!$result) {
				$messages[] = "<small>".mysql_error()."</small>";
			}
			elseif(mysql_affected_rows() == 0) {
				$messages[] = "Didn't affect any rows. This is odd behaviour.";
			}
			else {
				
				if(!empty($request["deletetags"])) {
					// Delete tags associated to this page
					$result = $db->query("DELETE FROM `pages_tags` WHERE slug='$page_escape'");
					
					// Error if it didn't work
					if(!$result) {
							$messages[] = "<small>".mysql_error()."</small>";
					}
				}
				
				// If all goes well, redirect to the deleted page
				// I don't know why.
				if(empty($messages))
					redirect($pages_dir.$page);
			}
		}
		
	}

	// ACTION : rename
	elseif($action == "rename") {
		if(!$page_array["isreal"]) {
			$messages[] = "This page doesn't exist in the database";
		}
		
		// Only rename if new name is posted
		elseif(isset($_POST["new"]))  // Commit edit
		{
			$newpage_escape = mysql_real_escape_string($_POST["new"]);
			
			// Do the renaming
			// Set slug to newpage wherever it was oldpage
			// Limit 1
			$result = $db->query("UPDATE pages SET slug='$newpage_escape'
										WHERE slug='$page_escape' LIMIT 1");
			
			if(!$result) {
				$messages[] = "<small>".mysql_error()."</small>";
			}
			elseif(mysql_affected_rows() == 0) {
				$messages[] = "Didn't affect any rows. This is odd behaviour.";
			}
			else {
				
				if(!empty($request["movetags"])) {
					// We have to move all the tags to the new page aswell
					// By this I mean rename the rows in the database
					$result = $db->query("UPDATE `pages_tags` SET slug='$newpage_escape'
											WHERE slug='$page_escape'");
					
					if(!$result) {
						$messages[] = "<small>".mysql_error()."</small>";
					}
				}
				if(!empty($request["movechilds"])) {
					// Move all child pages too
					$result = $db->query("UPDATE `pages_tags` SET tag='$newpage_escape'
											WHERE tag='$page_escape'");
				
					if(!$result) {
						$messages[] = "<small>".mysql_error()."</small>";
					}
				}
				
				// If both successful, redirect to the new page
				if(empty($messages))
					redirect($pages_dir.$_POST["new"]);
			}
		}
		
	}

	// ACTION : edit
	elseif($action == "edit") {
		if(isset($_POST["body"]))  // Commit edit
		{
			
			// Perform mysql_real_escape_string on all values
			$arraytoinsert = array_map("mysql_real_escape_string", array(
				"slug" => $page,
				"title" => stripslashes($_POST["title"]),
				"body" => stripslashes($_POST["body"]),
				"excerpt" => stripslashes($_POST["excerpt"]),
				"lastupdate" => time()
			));
			
			// Update or insert depends on if page is in database.
			// Hope to god database hasn't changed.
			$result = $page_array["isreal"] ? 
				$db->update_rows("UPDATE pages SET %s WHERE slug='$page_escape'", $arraytoinsert) :
				$db->insert_row("INSERT INTO pages %s VALUES %s", $arraytoinsert);
			
			if(!$result)
				$messages[] = "<small>".mysql_error()."</small>";
			else
			{
				// Page has been updated/created successfull, 
				// Add tags
				
				// Strip, explode, trim and remove empties
				// This makes string "    tag1,        tag2, ,   , tag3" into
				// array("tag1", "tag2", "tag3")
				$newtags = array_filter(array_map("trim", explode(",", stripslashes($_POST["tags"]))));
				
				// Use old tag array or an empty one
				$oldtags = $page_array["tags"] ? array_filter($page_array["tags"]) : array();
				
				// Collect all of $newtags which aren't in $oldtags
				$tagstoinsert = array_diff($newtags, $oldtags);
				
				// Collect all of $oldtags which aren't in $newtags
				$tagstodelete = array_diff($oldtags, $newtags);
				
				
				if(count($tagstoinsert) > 0)
				{
					// Create a string looking like ('mypage', 'tag1'), ('mypage', 'tag2'), ('mypage', 'tag3')
					$tagstr = implode(",", array_place('"(\''.$page_escape.'\', \'$v\')"', $tagstoinsert));
					
					if(!$db->query("INSERT INTO `pages_tags` (slug,tag) VALUES $tagstr"))
						$messages[] = "<small>".mysql_error()."</small>";
				}
				
				if(count($tagstodelete) > 0)
				{
					// This string will look like: tag1','tag2','tag3
					$tagstr = implode("','", $tagstodelete);
					
					if(!$db->query("DELETE FROM `pages_tags` WHERE slug='$page_escape' AND tag IN ('$tagstr')"))
						$messages[] = "<small>".mysql_error()."</small>";
				}
				
				
				
			}
			
			// No errors? redirect back to same page.
			if(count($messages) == 0)
				redirect("$pages_dir$page?edit");
		}
	}

	

}





// Close the database now.
// We're done with it
$db->close();

// Parse the HTML unless we already have HTML (Special pages)
if(!$page_array["html"])
{
	$parser or $parser = new ubbPagesParser();
	
	$parser->pagename = $page;
	$parser->allowHTML = true;
	$page_array["html"] = $parser->parse($page_array["body"]);
}

$time_taken = microtime_float() - $start_time;

// view.php takes 
include("./view.php");
?>
