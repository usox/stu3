<?php

declare(strict_types=1);

namespace Stu\Module\Control\Render;

use Noodlehaus\ConfigInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\Render\Fragments\RenderFragmentInterface;
use Stu\Module\Twig\TwigPageInterface;
use Stu\Orm\Entity\UserInterface;

/**
 * Executes the render chain for the site template
 *
 * Also registers a set of default variables for rendering
 */
final class GameTwigRenderer implements GameTwigRendererInterface
{
    private ConfigInterface $config;

    /** @var array<RenderFragmentInterface> */
    private array $renderFragments;

    /**
     * @param array<RenderFragmentInterface> $renderFragments
     */
    public function __construct(
        ConfigInterface $config,
        array $renderFragments
    ) {
        $this->config = $config;
        $this->renderFragments = $renderFragments;
    }

    public function render(
        GameControllerInterface $game,
        ?UserInterface $user,
        TwigPageInterface $twigPage
    ): string {
        $twigPage->setVar('GAME', $game);
        $twigPage->setVar('USER', $user);
        $twigPage->setVar('GAME_VERSION', $this->config->get('game.version'));
        $twigPage->setVar('WIKI', $this->config->get('wiki.base_url'));
        $twigPage->setVar('FORUM', $this->config->get('board.base_url'));
        $twigPage->setVar('CHAT', $this->config->get('discord.url'));
        $twigPage->setVar(
            'ASSET_PATHS',
            [
                'alliance' => $this->config->get('game.alliance_avatar_path'),
                'user' => $this->config->get('game.user_avatar_path'),
                'faction' => 'assets/rassen/',
            ]
        );

        // render fragments are user related, so render them only if a user is available
        if ($user !== null) {
            foreach ($this->renderFragments as $renderFragment) {
                $renderFragment->render($user, $twigPage);
            }
        }

        return $twigPage->render();
    }
}
