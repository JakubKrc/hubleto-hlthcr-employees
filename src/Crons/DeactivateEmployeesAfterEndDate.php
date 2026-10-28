<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Crons;

use Hubleto\App\External\WaiBlue\HlthcrEmployees\Models\Employee;

/**
 * Makes employees inactive once their end date has passed.
 */
class DeactivateEmployeesAfterEndDate extends \Hubleto\Erp\Cron
{
  public string $schedulingPattern = '10 00 * * *';

  public function run(): void
  {
    /** @var Employee */
    $mEmployee = $this->getModel(Employee::class);

    $deactivatedCount = $mEmployee->record
      ->where('is_active', 1)
      ->whereNotNull('date_terminated')
      ->where('date_terminated', '<', date('Y-m-d'))
      ->update(['is_active' => 0])
    ;

    $this->logger()->info('Employees deactivated after their end date: ' . $deactivatedCount);
  }
}
