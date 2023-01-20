<?php

declare(strict_types=1);

namespace Stu\Module\Trade\View\ShowLottery;

use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\LotteryTicketRepositoryInterface;

final class ShowLottery implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_LOTTERY';

    private LotteryTicketRepositoryInterface $lotteryTicketRepository;

    public function __construct(
        LotteryTicketRepositoryInterface $lotteryTicketRepository
    ) {
        $this->lotteryTicketRepository = $lotteryTicketRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->appendNavigationPart(
            'trade.php',
            _('Handel')
        );
        $game->appendNavigationPart(
            sprintf('trade.php?%s=1', static::VIEW_IDENTIFIER),
            _('Lotterie')
        );
        $game->setPageTitle(_('/ Handel / Nagus Lotterie'));
        $game->setTemplateFile('html/lottery.xhtml');

        $period = date("Y.m", time());
        $game->setTemplateVar('TICKETCOUNT', $this->lotteryTicketRepository->getAmountByPeriod($period));
    }
}
