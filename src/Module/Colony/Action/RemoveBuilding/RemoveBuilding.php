<?php

declare(strict_types=1);

namespace Stu\Module\Colony\Action\RemoveBuilding;

use ColonyData;
use request;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowColony\ShowColony;
use Stu\Orm\Entity\PlanetFieldInterface;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;

final class RemoveBuilding implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_REMOVE_BUILDING';

    private $colonyLoader;

    private $planetFieldRepository;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        PlanetFieldRepositoryInterface $planetFieldRepository
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->planetFieldRepository = $planetFieldRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $colony = $this->colonyLoader->byIdAndUser(
            request::indInt('id'),
            $game->getUser()->getId()
        );
        $game->setView(ShowColony::VIEW_IDENTIFIER);

        $field = $this->planetFieldRepository->getByColonyAndFieldId(
            $colony->getId(),
            (int)request::indInt('fid')
        );

        if ($field === null) {
            return;
        }

        if (!$field->hasBuilding()) {
            return;
        }
        if (!$field->getBuilding()->isRemoveAble()) {
            return;
        }
        $this->deActivateBuilding($field, $colony, $game);
        $colony->lowerMaxStorage($field->getBuilding()->getStorage());
        $colony->lowerMaxEps($field->getBuilding()->getEpsStorage());
        $game->addInformationf(
            _('%s auf Feld %d wurde demontiert'),
            $field->getBuilding()->getName(),
            $field->getFieldId()
        );
        $game->addInformation(_('Es konnten folgende Waren recycled werden'));
        foreach ($field->getBuilding()->getCosts() as $value) {
            $halfAmount = $value->getHalfAmount();
            if ($colony->getStorageSum() + $halfAmount > $colony->getMaxStorage()) {
                $amount = $colony->getMaxStorage() - $colony->getStorageSum();
            } else {
                $amount = $halfAmount;
            }
            if ($amount <= 0) {
                break;
            }
            $colony->upperStorage($value->getGoodId(), $amount);
            $game->addInformationf('%d %s', $amount, $value->getGood()->getName());
        }
        $field->clearBuilding();

        $this->planetFieldRepository->save($field);

        $colony->save();
    }

    protected function deActivateBuilding(PlanetFieldInterface $field, ColonyData $colony, GameControllerInterface $game)
    {
        if (!$field->hasBuilding()) {
            return;
        }
        if (!$field->isActivateAble()) {
            return;
        }
        if (!$field->isActive()) {
            return;
        }
        $colony->upperWorkless($field->getBuilding()->getWorkers());
        $colony->lowerWorkers($field->getBuilding()->getWorkers());
        $colony->lowerMaxBev($field->getBuilding()->getHousing());
        $field->setActive(0);

        $this->planetFieldRepository->save($field);

        $colony->save();
        $field->getBuilding()->postDeactivation($colony);

        $game->addInformationf(
            _('%s auf Feld %d wurde deaktiviert'),
            $field->getBuilding()->getName(),
            $field->getFieldId()
        );
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
