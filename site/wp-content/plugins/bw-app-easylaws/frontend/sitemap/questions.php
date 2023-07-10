<?php 
	require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );
	header('Content-type: application/xml');
	$t = DB()->prefix.'app_questions';
	$pages = DB()->get_results("SELECT ID, date_created, date_edited FROM $t WHERE status=1 AND trashed=0 ORDER BY date_created DESC");
?>
<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="<?php echo site_url('main-sitemap.xsl');?>"?>

<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<?php 
	foreach($pages as $p): 
		$id = $p->ID;
		$date = $p->date_edited ? $p->date_edited : $p->date_created;
	?>
	<url>
		<loc><?php echo site_url('question/'.$id);?></loc>
		<lastmod><?php echo date('c', $date);?></lastmod>
		<changefreq>weekly</changefreq>
		<priority>0.8</priority>
	</url>
	<?php endforeach;?>
</urlset>
