<?php

declare(strict_types=1);

namespace Stu\Module\PlayerSetting\Action\ActivateVacation;

use Stu\Lib\SessionInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ActivateVacation implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ACTIVATE_VACATION';
    
    private SessionInterface $session;

    private UserRepositoryInterface $userRepository;

    public function __construct(SessionInterface $session,
                                UserRepositoryInterface $userRepository) {
        $this->session = $session;
        $this->userRepository = $userRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        if ($game->getUser() === null) {
            return;
        }

        //wenn die letzte Aktivierung älter als eine Woche ist
        if ((time() - $game->getUser()->getVacationRequestDate()) > 604800)
        {
            $user = $game->getUser();

            $user->setVacationMode(true);
            $user->setVacationRequestDate(time());

            $this->userRepository->save($user);

            $this->session->logout();
        }
        else {
            $game->addInformation(
                _('Urlaubsmodus ist noch gesperrt. Letzte Aktivierung ist weniger als eine Woche her!')
            );
        }
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
