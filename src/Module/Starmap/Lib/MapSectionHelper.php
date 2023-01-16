<?php

declare(strict_types=1);

namespace Stu\Module\Starmap\Lib;

use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Entity\LayerInterface;
use UserYRow;

final class MapSectionHelper
{
    private const FIELDS_PER_SECTION = 20;

    public function setTemplateVars(
        GameControllerInterface $game,
        LayerInterface $layer,
        int $xCoordinate,
        int $yCoordinate,
        int $sectionId,
        string $module,
        string $viewIdentifier
    ): void {
        $layerId = $layer->getId();
        $layerWidth = $layer->getWidth();
        $layerHeight = $layer->getHeight();

        $maxx = $xCoordinate * self::FIELDS_PER_SECTION;
        $minx = $maxx - self::FIELDS_PER_SECTION + 1;
        $maxy = $yCoordinate * self::FIELDS_PER_SECTION;
        $miny = $maxy - self::FIELDS_PER_SECTION + 1;

        $fields = [];
        foreach (range($miny, $maxy) as $value) {
            $fields[] = new UserYRow($game->getUser(), $layerId, $value, $minx, $maxx);
        }

        $game->setTemplateVar('SECTION_ID', $sectionId);
        $game->setTemplateVar('HEAD_ROW', range($minx, $maxx));
        $game->setTemplateVar('MAP_FIELDS', $fields);

        if ($yCoordinate > 1) {
            $game->setTemplateVar(
                'NAV_UP',
                $this->constructPath(
                    $module,
                    $viewIdentifier,
                    $layerId,
                    $xCoordinate,
                    $yCoordinate > 1 ? $yCoordinate - 1 : 1,
                    $sectionId - 6
                )
            );
        }
        if ($yCoordinate * static::FIELDS_PER_SECTION < $layerHeight) {
            $game->setTemplateVar(
                'NAV_DOWN',
                $this->constructPath(
                    $module,
                    $viewIdentifier,
                    $layerId,
                    $xCoordinate,
                    $yCoordinate + 1 > $layerHeight / self::FIELDS_PER_SECTION ? $yCoordinate : $yCoordinate + 1,
                    $sectionId + 6
                )
            );
        }
        if ($xCoordinate > 1) {
            $game->setTemplateVar(
                'NAV_LEFT',
                $this->constructPath(
                    $module,
                    $viewIdentifier,
                    $layerId,
                    $xCoordinate > 1 ? $xCoordinate - 1 : 1,
                    $yCoordinate,
                    $sectionId - 1
                )
            );
        }
        if ($xCoordinate * static::FIELDS_PER_SECTION < $layerWidth) {
            $game->setTemplateVar(
                'NAV_RIGHT',

                $this->constructPath(
                    $module,
                    $viewIdentifier,
                    $layerId,
                    $xCoordinate + 1 > $layerWidth / self::FIELDS_PER_SECTION ? $xCoordinate : $xCoordinate + 1,
                    $yCoordinate,
                    $sectionId + 1
                )
            );
        }
    }

    private function constructPath(string $module, string $viewIdentifier, int $layerId, int $x, int $y, int $sectionId): string
    {
        return sprintf(
            '%s.php?%s=1&x=%d&y=%d&sec=%d&layerid=%d',
            $module,
            $viewIdentifier,
            $x,
            $y,
            $sectionId,
            $layerId
        );
    }
}
