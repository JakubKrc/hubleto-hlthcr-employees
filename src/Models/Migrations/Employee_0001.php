<?php

namespace Hubleto\App\External\WaiBlue\HlthcrEmployees\Models\Migrations;

use Hubleto\Framework\Migration;

class Employee_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("CREATE TABLE IF NOT EXISTS `hlthcr_employees` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) NOT NULL,
      `date_of_birth` date NULL DEFAULT NULL,
      `personal_number` varchar(255) NULL DEFAULT NULL,
      `id_branch` int(8) NULL DEFAULT NULL,
      `id_job` int(8) NULL DEFAULT NULL,
      `street` varchar(255) NULL DEFAULT NULL,
      `city` varchar(255) NULL DEFAULT NULL,
      `postal_code` varchar(255) NULL DEFAULT NULL,
      `email` varchar(255) NULL DEFAULT NULL,
      `phone` varchar(255) NULL DEFAULT NULL,
      `date_hired` date NULL DEFAULT NULL,
      `date_terminated` date NULL DEFAULT NULL,
      `note` text NULL DEFAULT NULL,
      `is_active` int(1) NOT NULL DEFAULT 1,
      INDEX `name_date_of_birth` (`name`, `date_of_birth`),
      INDEX `id_branch` (`id_branch`),
      INDEX `id_job` (`id_job`),
      INDEX `is_active` (`is_active`)
    ) ENGINE=InnoDB");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("DROP TABLE IF EXISTS `hlthcr_employees`");
  }

  public function upgradeForeignKeys(): void
  {
    // RESTRICT: a branch with employees (and later their medical exams) cannot be deleted by accident.
    $this->db->execute("ALTER TABLE `hlthcr_employees` ADD CONSTRAINT `fk__hlthcr_employees__id_branch` FOREIGN KEY (`id_branch`) REFERENCES `hlthcr_branches` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
    $this->db->execute("ALTER TABLE `hlthcr_employees` ADD CONSTRAINT `fk__hlthcr_employees__id_job` FOREIGN KEY (`id_job`) REFERENCES `hlthcr_jobs` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("ALTER TABLE `hlthcr_employees` DROP FOREIGN KEY `fk__hlthcr_employees__id_branch`");
    $this->db->execute("ALTER TABLE `hlthcr_employees` DROP FOREIGN KEY `fk__hlthcr_employees__id_job`");
  }
}
