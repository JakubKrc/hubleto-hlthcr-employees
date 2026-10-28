<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Controllers;

class Home extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->viewParams['archiveUrl'] = \Hubleto\App\External\WaiBlue\HlthcrEmployees\Loader::ARCHIVE_URL;
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEmployees/Home.twig');
  }
}
