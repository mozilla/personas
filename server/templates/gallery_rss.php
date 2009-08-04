<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
	<title><?printf(_("Personas - %s - %s"), $category, $tab);?></title>
<link><?= $path ?></link>
<description><?printf(_("Personas - %s - %s", $category, $tab);?></description>
<?php
	foreach ($list as &$persona)
	{
?>
	<item>
		<title><?= $persona['name'] ?></title>
		<link><?= $locale_conf->url('/persona/' . $persona['id']);?></link>
		<description><?= $persona['description'] ?></description>
		<media:content url="<?= $persona['header_url'] ?>" type="<?= $persona['media_type'] ?>">
			<media:title><?= $persona['name'] ?></media:title>
			<media:thumbnail url="<?= $persona['preview_url'] ?>" height="100" width="200"/>
		</media:content>
	</item>
<?php
	}
?>
</channel>
</rss>
