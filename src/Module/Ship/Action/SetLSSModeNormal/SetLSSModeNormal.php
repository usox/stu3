<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\SetLSSModeNormal;

use Override;
use request;
use Stu\Component\Ship\ShipLSSModeEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ActivatorDeactivatorHelperInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;

final class SetLSSModeNormal implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_SET_LSS_NORMAL';

    public function __construct(private ActivatorDeactivatorHelperInterface $helper)
    {
    }

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $this->helper->setLSSMode(request::indInt('id'), ShipLSSModeEnum::LSS_NORMAL, $game);
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
