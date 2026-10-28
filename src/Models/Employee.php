<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Models;

use Hubleto\App\External\WaiBlue\HlthcrCore\Models\Traits\EmptyDatesAsNull;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\Branch;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\Job;
use Hubleto\App\External\WaiBlue\HlthcrMedicalExams\Models\Exam;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Email;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Db\Column\Virtual;

/**
 * Employee of a client branch (not a Hubleto user). The job position decides the medical exam obligations.
 */
class Employee extends \Hubleto\App\External\WaiBlue\HlthcrCore\Models\HlthcrModel
{
  use EmptyDatesAsNull;
  public string $table = 'hlthcr_employees';
  public string $recordManagerClass = RecordManagers\Employee::class;
  public ?string $lookupSqlValue = 'concat({%TABLE%}.name, ifnull(concat(" (", date_format({%TABLE%}.date_of_birth, "%d.%m.%Y"), ")"), ""))';
  public ?string $lookupUrlDetail = 'hlthcr-employees/employees/{%ID%}';

  public array $relations = [
    'BRANCH' => [ self::BELONGS_TO, Branch::class, 'id_branch', 'id' ],
    'JOB' => [ self::BELONGS_TO, Job::class, 'id_job', 'id' ],
  ];

  public function describeColumns(): array
  {
    $columns = array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Name and surname')))->setRequired()->setDefaultVisible(),
      'date_of_birth' => (new Date($this, $this->translate('Date of birth')))->setRequired()->setDefaultVisible(),
      'personal_number' => (new Varchar($this, $this->translate('Personal number')))->setDefaultVisible(),
      'id_branch' => (new Lookup($this, $this->translate('Branch'), Branch::class))->setRequired()->setDefaultVisible(),
      'id_job' => (new Lookup($this, $this->translate('Job position'), Job::class, 'SET NULL'))->setDefaultVisible(),
      'virt_work_category' => (new Virtual($this, $this->translate('Work category')))
        ->setDefaultVisible()
        ->setProperty('sql', 'select (' . Job::getWorkCategorySql('hlthcr_employees.id_job') . ')')
      ,
      'street' => (new Varchar($this, $this->translate('Street'))),
      'city' => (new Varchar($this, $this->translate('City'))),
      'postal_code' => (new Varchar($this, $this->translate('Postal code'))),
      'email' => (new Email($this, $this->translate('E-mail'))),
      'phone' => (new Varchar($this, $this->translate('Phone'))),
      'date_hired' => (new Date($this, $this->translate('Start date')))->setDefaultVisible(),
      'date_terminated' => (new Date($this, $this->translate('End date')))
        ->setDefaultVisible()
        ->setHint($this->translate('The employee becomes inactive the day after the end date.'))
      ,
      'note' => (new Text($this, $this->translate('Note'))),
      // Derived on save (see onBeforeCreate/onBeforeUpdate), never edited by hand.
      'is_active' => (new Boolean($this, $this->translate('Active')))->setDefaultVisible()->setReadonly()->setDefaultValue(true),
    ]);

    return array_merge($columns, $this->describeExamColumns());
  }

  /**
   * Legacy employee list columns about medical exams. They read the table of the Exams app,
   * so they exist only when HlthcrMedicalExams is installed (it depends on this app, not the other way).
   */
  private function describeExamColumns(): array
  {
    $isExamsAppInstalled = $this->appManager()->getApp('Hubleto\\App\\External\\WaiBlue\\HlthcrMedicalExams\\Loader') !== null;
    if (!$isExamsAppInstalled) return [];

    // Only exams with a result count; entry and periodic exams decide the validity, as in the legacy list.
    $examTypesWithValidity = Exam::TYPE_ENTRY . ', ' . Exam::TYPE_PERIODIC;
    $noResult = Exam::RESULT_UNDETERMINED;
    return [
      'virt_last_exam_date' => (new Virtual($this, $this->translate('Last medical exam')))
        ->setDefaultVisible()
        ->setProperty('sql', 'select max(hlthcr_exams.date_exam) from hlthcr_exams
          where hlthcr_exams.id_employee = hlthcr_employees.id and hlthcr_exams.result <> ' . $noResult . '
          and hlthcr_exams.date_exam <= curdate()')
      ,
      'virt_exam_valid_to' => (new Virtual($this, $this->translate('Medical exam valid to')))
        ->setDefaultVisible()
        ->setProperty('sql', 'select hlthcr_exams.date_next_exam from hlthcr_exams
          where hlthcr_exams.id_employee = hlthcr_employees.id and hlthcr_exams.exam_type in (' . $examTypesWithValidity . ')
          and hlthcr_exams.result <> ' . $noResult . ' and hlthcr_exams.date_exam <= curdate()
          order by hlthcr_exams.date_exam desc limit 1')
      ,
    ];
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['addButtonText'] = $this->translate('Add employee');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);

    $description->addFilter(RecordManagers\Employee::STATUS_FILTER, [
      'title' => $this->translate('Active'),
      'options' => [
        RecordManagers\Employee::STATUS_FILTER_ACTIVE => $this->translate('Active'),
        RecordManagers\Employee::STATUS_FILTER_INACTIVE => $this->translate('Inactive (archive)'),
      ],
    ]);

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    return parent::describeForm();
  }

  public function getRelationsIncludedInLoadTableData(): array|null
  {
    return ['BRANCH', 'JOB'];
  }

  public function getMaxReadLevelForLoadTableData(): int
  {
    return 1;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    $record = $this->emptyDatesAsNull($record);
    $record['is_active'] = $this->isEmployeeActive($record);
    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    $record = $this->emptyDatesAsNull($record);
    $record['is_active'] = $this->isEmployeeActive($record);
    return $record;
  }

  /**
   * Active = the end date has not passed yet and the branch is active.
   * Both legacy apps made the employee inactive as soon as an end date was entered.
   */
  public function isEmployeeActive(array $record): bool
  {
    $endDate = (string) ($record['date_terminated'] ?? '');
    $endDateHasPassed = $endDate != '' && $endDate < date('Y-m-d');
    if ($endDateHasPassed) return false;

    $idBranch = (int) ($record['id_branch'] ?? 0);
    $branch = $this->getModel(Branch::class)->record->where('id', $idBranch)->first();
    $branchIsActive = $branch && (bool) $branch->is_active;

    return $branchIsActive;
  }
}
