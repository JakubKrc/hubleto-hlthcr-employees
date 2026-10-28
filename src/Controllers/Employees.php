<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Controllers;

class Employees extends \Hubleto\Erp\Controller
{
  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:HlthcrEmployees/Employees.twig');
  }
}
