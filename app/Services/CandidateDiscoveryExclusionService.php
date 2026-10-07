<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Merges spam-report and "don't show again" exclusions for discovery surfaces.
 */
class CandidateDiscoveryExclusionService
{
    public function __construct(
        private readonly ProfileSpamReportService $spamReports,
        private readonly ProfileHideService $hides
    ) {}

    /**
     * @return list<int>
     */
    public function excludedUserIdsForViewer(User $viewer): array
    {
        return array_values(
            array_unique(
                array_merge(
                    $this->spamReports->reportedUserIdsForViewer($viewer),
                    $this->hides->hiddenUserIdsForViewer($viewer)
                )
            )
        );
    }

    /**
     * @return list<string>
     */
    public function excludedUserUuidsForViewer(User $viewer): array
    {
        return array_values(
            array_unique(
                array_merge(
                    $this->spamReports->reportedUserUuidsForViewer($viewer),
                    $this->hides->hiddenUserUuidsForViewer($viewer)
                )
            )
        );
    }
}
