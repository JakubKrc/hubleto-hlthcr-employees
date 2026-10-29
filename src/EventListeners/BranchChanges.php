<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\EventListeners;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\Branch;
use Hubleto\App\External\WaiBlue\HlthcrEmployees\Models\Employee;
use Hubleto\Framework\Interfaces\ModelInterface;

/**
 * Deactivating a branch or a client deactivates its employees, like the legacy app did.
 * Reactivating a branch makes its employees active again unless their end date has passed.
 */
class BranchChanges extends \Hubleto\Framework\EventListener implements \Hubleto\Framework\Interfaces\EventListenerInterface
{
  public function onModelAfterUpdate(ModelInterface $model, array $originalRecord, array $savedRecord): void
  {
    if (!($model instanceof Branch) && !($model instanceof Customer)) return;

    $wasActive = (bool) ($originalRecord['is_active'] ?? true);
    $isActive = (bool) ($savedRecord['is_active'] ?? $wasActive);

    /** @var Employee */
    $mEmployee = $this->getModel(Employee::class);

    $branchWasReactivated = $model instanceof Branch && !$wasActive && $isActive;
    if ($branchWasReactivated) {
      $mEmployee->record
        ->where('id_branch', (int) $savedRecord['id'])
        ->where(function ($employeesStillEmployed) {
          $employeesStillEmployed->whereNull('date_terminated')->orWhere('date_terminated', '>=', date('Y-m-d'));
        })
        ->update(['is_active' => 1]);
      return;
    }

    $wasDeactivated = $wasActive && !$isActive;
    if (!$wasDeactivated) return;

    if ($model instanceof Branch) {
      $mEmployee->record->where('id_branch', (int) $savedRecord['id'])->update(['is_active' => 0]);
      return;
    }

    // Customer: the branch cascade (HlthcrBranches) updates branches without model events, so cover employees here.
    $idCustomer = (int) $savedRecord['id'];
    $mEmployee->record
      ->whereIn('id_branch', function ($branchesOfCustomer) use ($idCustomer) {
        $branchesOfCustomer->select('id')->from('hlthcr_branches')->where('id_customer', $idCustomer);
      })
      ->update(['is_active' => 0]);
  }
}
