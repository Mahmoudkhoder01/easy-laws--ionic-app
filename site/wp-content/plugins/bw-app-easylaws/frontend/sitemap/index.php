<?php 
	require_once( explode( 'wp-content', __FILE__ )[0] . 'wp-load.php' );
	header('Content-type: application/xml');
?>
<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="<?php echo site_url('main-sitemap.xsl');?>"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc><?php echo site_url('pages-sitemap.xml');?></loc>
        <lastmod><?php echo date('c');?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?php echo site_url('subjects-sitemap.xml');?></loc>
        <lastmod><?php echo date('c');?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?php echo site_url('questions-sitemap.xml');?></loc>
        <lastmod><?php echo date('c');?></lastmod>
    </sitemap>
</sitemapindex>
