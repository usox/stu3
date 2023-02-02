<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\View\Diplomatic;

use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use JBBCode\Parser;
use Noodlehaus\ConfigInterface;
use Stu\Component\Alliance\AllianceEnum;
use Stu\Module\Alliance\View\AllianceList\AllianceList;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\AllianceRelationRepositoryInterface;
use Stu\Orm\Repository\AllianceRepositoryInterface;

final class DiplomaticRelations implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_DIPLOMATIC_RELATIONS';

    private AllianceRepositoryInterface $allianceRepository;

    private Parser $bbCodeParser;

    private AllianceRelationRepositoryInterface $allianceRelationRepository;

    private ConfigInterface $config;

    public function __construct(
        AllianceRepositoryInterface $allianceRepository,
        Parser $bbCodeParser,
        AllianceRelationRepositoryInterface $allianceRelationRepository,
        ConfigInterface $config
    ) {
        $this->allianceRepository = $allianceRepository;
        $this->bbCodeParser = $bbCodeParser;
        $this->allianceRelationRepository = $allianceRelationRepository;
        $this->config = $config;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setPageTitle('Diplomatische Verbindungen');

        $game->appendNavigationPart(
            'alliance.php',
            'Allianz'
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?%s=1',
                AllianceList::VIEW_IDENTIFIER
            ),
            'Allianzliste'
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?%s=1',
                static::VIEW_IDENTIFIER,
            ),
            'Diplomatische Verbindungen'
        );

        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.charset', 'UTF-8');
        $graph->setAttribute('graphviz.graph.bgcolor', '#121220');
        $graph->setAttribute('graphviz.graph.size', '800,600');
        $vertexes = [];

        foreach ($this->allianceRepository->findAll() as $alliance) {
            $vertex = $graph->createVertex($alliance->getId());
            $name = $this->bbCodeParser->parse($alliance->getName())->getAsText();
            $name = str_replace(['&', '<', '>', '"', "'", '\\', "\n"], '', $name);
            $vertex->setAttribute('graphviz.label', $name);
            $vertex->setAttribute('graphviz.fontcolor', '#9d9d9d');
            $vertex->setAttribute('graphviz.shape', 'box');
            $vertex->setAttribute('graphviz.color', '#4b4b4b');
            $vertex->setAttribute(
                'graphviz.href',
                sprintf(
                    '%s/alliance.php?SHOW_ALLIANCE&id=%d',
                    $this->config->get('game.base_url'),
                    $alliance->getId()
                )
            );
            $vertex->setAttribute('graphviz.target', '_blank');

            $vertexes[$alliance->getId()] = $vertex;
        }

        foreach ($this->allianceRelationRepository->findAll() as $relation) {
            $allianceId = $relation->getAllianceId();
            $opponentId = $relation->getOpponentId();

            if (!array_key_exists($allianceId, $vertexes) || !array_key_exists($opponentId, $vertexes)) {
                continue;
            }

            switch ($relation->getType()) {
                case AllianceEnum::ALLIANCE_RELATION_WAR:
                    $color = '#810800';
                    break;
                case AllianceEnum::ALLIANCE_RELATION_TRADE:
                    $color = '#a5a200';
                    break;
                case AllianceEnum::ALLIANCE_RELATION_PEACE:
                    $color = '#004608';
                    break;
                case AllianceEnum::ALLIANCE_RELATION_ALLIED:
                    $color = '#005183';
                    break;
                case AllianceEnum::ALLIANCE_RELATION_FRIENDS:
                    $color = '#5cb762';
                    break;
                case AllianceEnum::ALLIANCE_RELATION_VASSAL:
                    $color = '#008392';
                    break;
                default:
                    $color = '#ffffff';
            }

            $edge = $vertexes[$allianceId]->createEdge($vertexes[$opponentId]);
            $edge->setAttribute('graphviz.color', $color);
            $edge->setAttribute('graphviz.tooltip', $relation->getTypeDescription());
            $edge->setAttribute('graphviz.penwidth', 2);
        }

        $graphviz = new GraphViz();
        $graphviz->setFormat('svg');

        $game->setTemplateFile('html/alliance_diplomatic_relations.xhtml');
        $game->setTemplateVar(
            'RELATIONS_IMAGE',
            $graphviz->createImageHtml($graph)
        );
    }
}
