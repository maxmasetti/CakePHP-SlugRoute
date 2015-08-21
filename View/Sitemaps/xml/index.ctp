<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
        xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo Router::url('/',true); ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
<?php foreach ($urls as $url):?>
    <url>
        <loc><?php echo Router::url($url['url'], true); ?></loc>
        <lastmod><?php echo $this->Time->toAtom($url['mod']); ?></lastmod>
        <priority><?php echo $url['pri']; ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
