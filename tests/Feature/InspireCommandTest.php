<?php

use App\Commands\InspireCommand;

it('inspires artisans', function () {
    $this->artisan(InspireCommand::class)
        ->assertExitCode(0);
});
