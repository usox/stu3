<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\View\Boards;

use Stu\Exception\AccessViolation;
use Stu\Module\Alliance\Lib\AllianceActionManagerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\AllianceBoardRepositoryInterface;

final class Boards implements ViewControllerInterface
{
    /**
     * @var string
     */
    public const VIEW_IDENTIFIER = 'SHOW_BOARDS';

    private AllianceBoardRepositoryInterface $allianceBoardRepository;

    private AllianceActionManagerInterface $allianceActionManager;

    public function __construct(
        AllianceBoardRepositoryInterface $allianceBoardRepository,
        AllianceActionManagerInterface $allianceActionManager
    ) {
        $this->allianceBoardRepository = $allianceBoardRepository;
        $this->allianceActionManager = $allianceActionManager;
    }

    public function handle(GameControllerInterface $game): void
    {
        $alliance = $game->getUser()->getAlliance();

        if ($alliance === null) {
            throw new AccessViolation();
        }

        $allianceId = $alliance->getId();

        $game->setPageTitle(_('Allianzforum'));
        $game->appendNavigationPart(
            'alliance.php',
            _('Allianz')
        );
        $game->appendNavigationPart(
            'alliance.php?SHOW_BOARDS=1',
            _('Forum')
        );
        $game->setTemplateFile('html/allianceboard.xhtml');

        $game->setTemplateVar(
            'BOARDS',
            $this->allianceBoardRepository->getByAlliance($allianceId)
        );
        $game->setTemplateVar(
            'EDITABLE',
            $this->allianceActionManager->mayEdit($alliance, $game->getUser())
        );
    }
}
