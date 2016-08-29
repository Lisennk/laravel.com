<?php

namespace App\Http\Controllers;

use App\Documentation;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Documentation controller
 *
 * @package App\Http\Controllers
 */
class DocsController extends Controller
{
    /**
     * The documentation repository.
     *
     * @var Documentation
     */
    protected $docs;

    /**
     * Create a new controller instance.
     *
     * @param  Documentation  $docs
     * @return void
     */
    public function __construct(Documentation $docs)
    {
        $this->docs = $docs;
    }

    /**
     * Show the root documentation page (/docs).
     *
     * @return Response
     */
    public function showRootPage()
    {
        return redirect('docs/'.DEFAULT_VERSION);
    }

    /**
     * Show a documentation page.
     *
     * @param  string $version
     * @param  string|null $page
     * @return Response
     */
    public function show($version, $page = null)
    {
        if (! $this->isVersion($version)) {
            return redirect('docs/'.DEFAULT_VERSION.'/'.$version, 301);
        } elseif (! defined('CURRENT_VERSION')) {
            define('CURRENT_VERSION', $version);
        }

        $sectionPage = $page ?: 'installation';
        $content = $this->docs->get($version, $sectionPage) ?: abort(404);

        return view('docs', [
            'title' => $this->getTitleFromContent($content),
            'index' => $this->docs->getIndex($version),
            'content' => $content,
            'currentVersion' => $version,
            'versions' => Documentation::getDocVersions(),
            'currentSection' => $this->getSectionOrRedirectToDefault($version, $page),
            'canonical' => $this->getCanonical($sectionPage),
        ]);
    }

    /**
     * Returns title from content h1 tag
     *
     * @param $content
     * @return string
     */
    protected function getTitleFromContent($content)
    {
        $parsed = (new Crawler($content))->filterXPath('//h1');
        return count($parsed) ? $parsed->text() : null;
    }

    /**
     * Returns canonical
     *
     * @param $sectionPage
     * @return null|string
     */
    protected function getCanonical($sectionPage)
    {
        if ($this->docs->sectionExists(DEFAULT_VERSION, $sectionPage))
            return 'docs/'.DEFAULT_VERSION.'/'.$sectionPage;

        return null;
    }

    /**
     * Returns section or redirects to default documentation page
     *
     * @param $version
     * @param $page
     * @return string
     */
    protected function getSectionOrRedirectToDefault($version, $page)
    {
        $section = $this->getSection($version, $page);
        if (!$section && !is_null($page)) $this->redirectToDefault($version);
        return $section;
    }

    /**
     * Returns section
     *
     * @param $version
     * @param $page
     * @return string
     */
    protected function getSection($version, $page)
    {
        if ($this->docs->sectionExists($version, $page)) return '/'.$page;
        return '';
    }

    /**
     * Redirects to default documentation page
     *
     * @param $version
     */
    protected function redirectToDefault($version)
    {
        abort(302, '', ['Location' => '/docs/'.$version]);
    }

    /**
     * Determine if the given URL segment is a valid version.
     *
     * @param  string  $version
     * @return bool
     */
    protected function isVersion($version)
    {
        return in_array($version, array_keys(Documentation::getDocVersions()));
    }
}