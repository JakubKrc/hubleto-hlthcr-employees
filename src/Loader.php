<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees;

use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\Branch;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\Job;
use Hubleto\App\External\WaiBlue\HlthcrCore\Services\DemoData;

class Loader extends \Hubleto\App\External\WaiBlue\HlthcrCore\HlthcrApp
{
  const ARCHIVE_URL = 'hlthcr-employees/employees?filters['
    . Models\RecordManagers\Employee::STATUS_FILTER . ']='
    . Models\RecordManagers\Employee::STATUS_FILTER_INACTIVE;

  public function init(): void
  {
    \Hubleto\App\External\WaiBlue\HlthcrCore\Services\Translator::register();
    parent::init();

    $this->router()->get([
      '/^hlthcr-employees\/?$/' => Controllers\Home::class,
      '/^hlthcr-employees\/settings\/?$/' => Controllers\Settings::class,
    ]);
    $this->router()->crud('hlthcr-employees/employees', Controllers\Employees::class);

    $this->addSearchSwitch('z', 'employees');

    $this->eventManager()->addEventListener('onModelAfterUpdate', $this->getService(EventListeners\BranchChanges::class));

    $this->cronManager()->addCron(Crons\DeactivateEmployeesAfterEndDate::class);
  }

  public function installApp(int $round): void
  {
    \Hubleto\App\External\WaiBlue\HlthcrCore\Services\Translator::register();

    if ($round == 1) {
      $this->getModel(Models\Employee::class)->upgradeSchema();
      // "php hubleto app install" runs only round 1, so foreign keys are created here too (Hubleto skips them later).
      $this->installForeignKeys();
    }
  }

  public function search(array $expressions): array
  {
    $employeesQuery = $this->getModel(Models\Employee::class)->record->prepareReadQuery();

    foreach ($expressions as $expression) {
      $employeesQuery = $employeesQuery->where(function ($matchingEmployee) use ($expression) {
        $matchingEmployee->orWhere('hlthcr_employees.name', 'like', '%' . $expression . '%');
        $matchingEmployee->orWhere('hlthcr_employees.personal_number', 'like', '%' . $expression . '%');
      });
    }

    $results = [];
    foreach ($employeesQuery->limit(20)->get()->toArray() as $employee) {
      $results[] = [
        'id' => $employee['id'],
        'label' => $employee['name'],
        'url' => 'hlthcr-employees/employees/' . $employee['id'],
        'description' => $employee['personal_number'] ?? '',
      ];
    }
    return $results;
  }

  public function renderSecondSidebar(): string
  {
    return '
      ' . $this->secondSidebarTitle() . '
      <div class="app-sidebar-buttons">
        ' . $this->secondSidebarButton('hlthcr-employees/employees', 'fas fa-users', $this->translate('Employees')) . '
        ' . $this->secondSidebarButton(self::ARCHIVE_URL, 'fas fa-box-archive', $this->translate('Archive')) . '
        ' . $this->secondSidebarButton('hlthcr-employees/settings', 'fas fa-cog', $this->translate('Settings')) . '
      </div>
    ';
  }

  public function renderAlerts(): string
  {
    return '';
  }

  public function getSidebarBadgeNumber(): int
  {
    return 0;
  }

  public function generateDemoData(): void
  {
    /** @var Models\Employee */
    $mEmployee = $this->getModel(Models\Employee::class);
    /** @var Job */
    $mJob = $this->getModel(Job::class);

    // Own (made-up) names per client, so lists across clients do not look like duplicates.
    // The e2e tests use the names of the first client.
    $demoNamesByBranchIndex = [
      ['Ján Ukážkový', 'Eva Vzorová', 'Peter Testovací', 'Mária Fiktívna', 'Tomáš Príkladný'],
      ['Jozef Vymyslený', 'Anna Pokusná', 'Martin Skúšobný', 'Zuzana Nácviková', 'Lukáš Náhodný'],
      ['Róbert Zástupný', 'Katarína Modelová', 'Michal Ilustračný', 'Lucia Šablónová', 'Filip Cvičný'],
      ['Pavol Predlohový', 'Jana Ukážková', 'Igor Vzorný', 'Monika Fiktívna', 'Daniel Testovací'],
    ];

    // Only the first branch of each demo client (created by the Branches demo data), not branches added later.
    $demoCompanyIds = $this->getService(DemoData::class)->getDemoCompanyIds();
    $demoCustomerIds = $this->getModel(Customer::class)->record->whereIn('company_id', $demoCompanyIds)->pluck('id');

    $demoBranches = [];
    foreach ($demoCustomerIds as $idDemoCustomer) {
      $firstBranch = $this->getModel(Branch::class)->record->where('id_customer', $idDemoCustomer)->orderBy('id')->first();
      if ($firstBranch) $demoBranches[] = $firstBranch;
    }

    foreach ($demoBranches as $branchIndex => $branch) {
      $demoNames = $demoNamesByBranchIndex[$branchIndex % count($demoNamesByBranchIndex)];

      $branchAlreadyHasEmployees = $mEmployee->record->where('id_branch', $branch->id)->exists();
      if ($branchAlreadyHasEmployees) continue;

      $jobsOfBranch = $mJob->record->where('id_branch', $branch->id)->orderBy('id')->get();
      if (count($jobsOfBranch) == 0) continue;

      foreach ($demoNames as $index => $demoName) {
        $job = $jobsOfBranch[$index % count($jobsOfBranch)];
        // The last demo employee has left the company, so the archive is not empty.
        $hasLeft = $index == count($demoNames) - 1;

        $ageInYears = 25 + $index * 7;
        $extraDaysOfAge = $index * 40;
        $dateOfBirth = date('Y-m-d', strtotime('-' . $ageInYears . ' years -' . $extraDaysOfAge . ' days'));

        $mEmployee->record->recordCreate([
          'name' => $demoName,
          'date_of_birth' => $dateOfBirth,
          'personal_number' => 'D' . $branch->id . '-' . ($index + 1),
          'id_branch' => $branch->id,
          'id_job' => $job->id,
          'city' => $branch->city,
          'email' => $this->getDemoEmail($demoName, (int) $branch->id),
          'date_hired' => date('Y-m-d', strtotime('-' . ($index + 1) . ' years')),
          'date_terminated' => $hasLeft ? date('Y-m-d', strtotime('-1 month')) : null,
        ]);
      }
    }
  }

  // FAKE address that can never be delivered (example.com), e.g. "jan-ukazkovy.3@example.com".
  private function getDemoEmail(string $demoName, int $idBranch): string
  {
    return \Hubleto\Framework\Helper::str2url($demoName) . '.' . $idBranch . '@example.com';
  }
}
