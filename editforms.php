<?php
if($iseditting || $action == "view"):
$tagstr = implode(",", $page_array["tags"]);
$encoded_title = $page_array["title"] ? htmlspecialchars($page_array["title"]) : $page_array["slug"];
?>
<form method="post" id="editform" action="<?php echo $page_link; ?>?edit">
	<p>
		Title
		<input type="text" name="title" class="textinput" value="<?php echo $encoded_title ?>" />
	</p>
	<p>
		Body
		<textarea name="body" class="areainput"><?php echo htmlspecialchars($page_array["body"]); ?></textarea>
	</p>
	<p></p>
	<div id="editmore" class="hidden">
		<p>Tags <input type="text" name="tags" class="textinput" value="<?php echo htmlspecialchars($tagstr); ?>" /></p>
		<p>Excerpt<textarea name="excerpt" class="areainput smallarea"><?php echo htmlspecialchars($page_array["excerpt"]); ?></textarea></p>
	</div>
	<p>
<?php if($iseditting): ?>
		<input type="submit" value="Save" class="button" />
<?php endif; ?>
		<a href="javascript:;" onclick="if(!toggleclass('editmore', 'hidden')) scrollmore('editmore');">more</a>,
<?php if($iseditting): ?>
		<a href="<?php echo $page_link; ?>?rename">rename</a>,
		<a href="<?php echo $page_link; ?>?delete">delete</a>,
<?php endif; ?>
		<a href="<?php echo $page_link; ?>">cancel</a>
	</p>
	<input type="hidden" name="page" value="<?php echo $page_array["slug"]; ?>" />
</form>

<?php elseif($action == "rename"): ?>

<form method="post" id="editform" action="<?php echo $page_link; ?>?rename">
	<p>
		<input type="text" name="new" class="textinput" value="<?php echo $page_array["slug"]; ?>"><br>
		<label><input type="checkbox" name="movetags" checked="true"> Move tags</label><br>
		<label><input type="checkbox" name="movechilds" checked="true"> Move children</label>
	</p>
	<input type="submit" value="Rename" class="button">
	 <a href="<?php echo $page_link; ?>">cancel</a></p>
	<input type="hidden" name="page" value="<?php echo $page_array["slug"]; ?>">
</form>

<?php elseif($action == "delete"): ?>

<form method="post" id="editform" action="<?php echo $page_link; ?>?delete">
	<p><label><input type="checkbox" name="deletetags" checked="true"> Delete tags</label></p>
	<input type="hidden" name="confirm" value="1">
	<input type="submit" value="Delete" class="button">
	 <a href="<?php echo $page_link; ?>">cancel</a></p>
	<input type="hidden" name="page" value="<?php echo $page_array["slug"]; ?>">
</form>

<?php endif; ?>
