# Hlthcr Employees

Employees of the branches with end date, archive and the daily end-date job.

Hubleto app of the Hlthcr set (occupational health service, Slovakia). Namespace: `Hubleto\App\External\WaiBlue\HlthcrEmployees`.
Needs: `wai-blue/hubleto-hlthcr-core`, `wai-blue/hubleto-hlthcr-branches`.

## Install into a Hubleto project

```bash
composer config repositories.wai-blue/hubleto-hlthcr-employees vcs <repository-url>
composer require wai-blue/hubleto-hlthcr-employees:dev-main
php hubleto app install "Hubleto\App\External\WaiBlue\HlthcrEmployees"
npm run build
```
