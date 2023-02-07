<?php

declare(strict_types=1);

namespace Stu\Module\Database\View\DiscovererRating;

use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Database\Lib\DatabaseTopListDiscover;
use Stu\Orm\Repository\DatabaseUserRepositoryInterface;

final class DiscovererRanking implements ViewControllerInterface
{

    public const VIEW_IDENTIFIER = 'SHOW_TOP_DISCOVER';

    private DatabaseUserRepositoryInterface $databaseUserRepository;

    public function __construct(
        DatabaseUserRepositoryInterface $databaseUserRepository
    ) {
        $this->databaseUserRepository = $databaseUserRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->appendNavigationPart(
            'database.php',
            'Datenbank'
        );
        $game->appendNavigationPart(
            sprintf(
                'database.php?%s=1',
                static::VIEW_IDENTIFIER
            ),
            'Die 10 besten Entdecker'
        );
        $game->setPageTitle('/ Datenbank / Die 10 besten Entdecker');
        $game->showMacro('html/database.xhtml/top_research_user');
        $game->setTemplateVar(
            'DISCOVERER_LIST',

            array_map(
                fn (array $data): DatabaseTopListDiscover => new DatabaseTopListDiscover($data),
                $this->databaseUserRepository->getTopList()
            )
        );
        $game->setTemplateVar('USER_COUNT', $this->databaseUserRepository->getCountForUser($game->getUser()->getId()));
    }
}
