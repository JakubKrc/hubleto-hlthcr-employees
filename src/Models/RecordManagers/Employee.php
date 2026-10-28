<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Models\RecordManagers;

use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\RecordManagers\Branch;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\RecordManagers\Job;
use Hubleto\App\External\WaiBlue\HlthcrBranches\Models\RecordManagers\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends \Hubleto\Erp\RecordManager
{
  use BelongsToBranch;

  public $table = 'hlthcr_employees';

  // Options of the status filter in the table (URL filters[STATUS_FILTER]=<option>).
  const STATUS_FILTER = 'fEmployeeStatus';
  const STATUS_FILTER_ACTIVE = 0;
  const STATUS_FILTER_INACTIVE = 1;

  public function BRANCH(): BelongsTo
  {
    return $this->belongsTo(Branch::class, 'id_branch', 'id');
  }

  public function JOB(): BelongsTo
  {
    return $this->belongsTo(Job::class, 'id_job', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    return $this->restrictToVisibleBranches($query);
  }

  public function prepareLookupQuery(string $search): mixed
  {
    $query = parent::prepareLookupQuery($search);

    // Dropdowns offer active employees only, as in both legacy apps (a chosen one still shows in its form).
    $query = $query->where($this->table . '.is_active', 1);
    return $this->restrictToVisibleBranches($query);
  }

  public function addUrlFiltersToQuery(mixed $query): mixed
  {
    $query = parent::addUrlFiltersToQuery($query);

    $query = $this->filterByBranchFromUrl($query);

    $hubleto = \Hubleto\Erp\Loader::getGlobalApp();
    $filters = $hubleto->router()->urlParamAsArray('filters');
    if (isset($filters[self::STATUS_FILTER])) {
      $showInactive = (int) $filters[self::STATUS_FILTER] == self::STATUS_FILTER_INACTIVE;
      $query = $query->where($this->table . '.is_active', $showInactive ? 0 : 1);
    }

    return $query;
  }

  public function prepareLookupData(array $dataRaw): array
  {
    return parent::prepareLookupData($dataRaw);
  }
}
