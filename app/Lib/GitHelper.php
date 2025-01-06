<?php

namespace App\Lib;

class GitHelper
{
    public function getCommits(): array
    {
        exec('git log --pretty="%s" -n15 HEAD', $commits);

        return $commits;
    }
}
