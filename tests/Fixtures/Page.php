<?php

declare(strict_types=1);

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree
{
}

/**
 * @extends ContentController<Page>
 */
class PageController extends ContentController
{
}
