<?php

require_once __DIR__ . '/Maintenance.php';

class GenerateSitemap extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->mDescription = 'Generates sitemaps for Main, File, and User namespaces';
        $this->addOption('dir', 'Directory to save sitemap files in', false, true);
        $this->setBatchSize(500);
    }

    public function execute() {
        $dir = $this->getOption('dir');
        if (!$dir) {
            $this->error('Please specify a directory with --dir');
        }

        $nsList = [NS_MAIN, NS_FILE, NS_USER];
        foreach ($nsList as $ns) {
            $sitemap = $this->generateSitemap($ns);
            $filename = $dir . '/sitemap-' . $this->getNamespaceName($ns) . '.xml';
            file_put_contents($filename, $sitemap);
        }
    }

    private function generateSitemap($ns) {
        $pages = $this->getPagesInNamespace($ns);
        $urls = array_map([$this, 'getPageUrl'], $pages);
        $xml = $this->generateXml($urls);
        return $xml;
    }

    private function getPagesInNamespace($ns) {
        $dbr = wfGetDB(DB_REPLICA);
        $pages = [];
        $res = $dbr->select(
            'page',
            ['page_title'],
            ['page_namespace' => $ns, 'page_is_redirect' => 0],
            __METHOD__,
            ['ORDER BY' => 'page_title']
        );
        foreach ($res as $row) {
            $pages[] = $row->page_title;
        }
        return $pages;
    }

    private function getPageUrl($title) {
        return wfExpandUrl(wfTitleToUrl($title), PROTO_HTTPS);
    }

    private function generateXml($urls) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            $xml .= '</url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";
        return $xml;
    }
}

$maintClass = GenerateSitemap::class;
require_once RUN_MAINTENANCE_IF_MAIN;
