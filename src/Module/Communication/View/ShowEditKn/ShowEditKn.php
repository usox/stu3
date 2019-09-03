<?php

declare(strict_types=1);

namespace Stu\Module\Communication\View\ShowEditKn;

use AccessViolation;
use RPGPlot;
use Stu\Module\Communication\Action\EditKnPost\EditKnPost;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Entity\KnPostInterface;
use Stu\Orm\Repository\KnPostRepositoryInterface;

final class ShowEditKn implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'EDIT_KN';

    private $showEditKnRequest;

    private $knPostRepository;

    public function __construct(
        ShowEditKnRequestInterface $showEditKnRequest,
        KnPostRepositoryInterface $knPostRepository
    ) {
        $this->showEditKnRequest = $showEditKnRequest;
        $this->knPostRepository = $knPostRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        /** @var KnPostInterface $post */
        $post = $this->knPostRepository->find($this->showEditKnRequest->getPostId());

        if (
            $post === null ||
            $post->getUserId() !== $game->getUser()->getId() ||
            $post->getDate() < time() - EditKnPost::EDIT_TIME
        ) {
            throw new AccessViolation();
        }

        $game->setTemplateFile('html/editkn.xhtml');
        $game->appendNavigationPart('comm.php', _('KommNet'));
        $game->appendNavigationPart(
            sprintf('comm.php?%s=1', static::VIEW_IDENTIFIER),
            _('Beitrag bearbeiten')
        );
        $game->setPageTitle(_('Beitrag bearbeiten'));

        $game->setTemplateVar(
            'ACTIVE_RPG_PLOTS',
            RPGPlot::getObjectsBy(
                sprintf(
                    "WHERE end_date=0 AND id IN (SELECT plot_id FROM stu_plots_members WHERE user_id=%d) ORDER BY start_date DESC",
                    $game->getUser()->getId()
                )
            )
        );
        $game->setTemplateVar('POST', $post);
    }
}
