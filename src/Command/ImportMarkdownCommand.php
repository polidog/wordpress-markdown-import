<?php

namespace App\Command;

use App\Feature\WordPressImport\Content;
use App\Feature\WordPressImport\ContentLoader;
use App\Feature\WordPressImport\ContentParser;
use App\Feature\WordPressImport\WordPressUploader;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-markdown',
    description: 'Hugoの記事をWordPressに移植するためのコマンド',
)]
final class ImportMarkdownCommand extends Command
{
    public function __construct(
        private readonly ContentLoader $contentLoader,
        private readonly WordPressUploader $wordPressUploader,
        private readonly ContentParser $contentParser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Markdownファイルが入っているパス')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $io = new SymfonyStyle($input, $output);
        try {
            foreach ($this->contentLoader->load($path) as $path => $content) {
                $content = Content::newInstance($this->contentParser, $content);
                $this->wordPressUploader->upload($content);
                $io->success(sprintf('Uploaded: %s', $content->title));
            }
        } catch (ClientException $ce) {
            $content = $ce->getResponse()->getBody()->getContents();
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $io->error($json['message']);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
