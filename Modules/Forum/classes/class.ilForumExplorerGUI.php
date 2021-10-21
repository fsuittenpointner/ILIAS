<?php declare(strict_types=1);

/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\UI\Component\Tree\Node\Node;
use ILIAS\UI\Component\Tree\Tree;

/**
 * Class ilForumExplorerGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilForumExplorerGUI extends ilTreeExplorerGUI
{
    protected ilForumTopic $thread;
    protected ilForumPost $root_node;
    protected array $node_id_to_parent_node_id_map = [];
    protected int $max_entries = PHP_INT_MAX;
    protected array $preloaded_children = [];

    /** @var ilForumAuthorInformation[] */
    protected array $authorInformation = [];
    protected int $currentPostingId = 0;

    /** @var int */
    private $currentPage = 0;

    public function __construct(
        string $a_expl_id,
        object $a_parent_obj,
        string $a_parent_cmd,
        ilForumTopic $thread,
        ilForumPost $root
    ) {
        global $DIC;

        parent::__construct($a_expl_id, $a_parent_obj, $a_parent_cmd, $DIC->repositoryTree());

        $this->setSkipRootNode(false);
        $this->setAjax(false);
        $this->setPreloadChilds(true);

        $this->thread = $thread;
        $this->root_node = $root;

        $this->ctrl->setParameter($this->parent_obj, 'thr_pk', $this->thread->getId());

        $frm = new ilForum();
        $this->max_entries = $frm->getPageHits();

        $this->initPosting();

        $this->setNodeOpen($this->root_node->getId());
    }

    protected function initPosting() : void
    {
        $postingId = (int) ($this->httpRequest->getParsedBody()['pos_pk'] ?? 0);
        if (0 === $postingId) {
            $postingId = (int) ($this->httpRequest->getQueryParams()['pos_pk'] ?? 0);
        }

        $this->currentPostingId = $postingId;
    }

    public function getChildsOfNode($a_parent_node_id) : array
    {
        if ($this->preloaded) {
            if (isset($this->preloaded_children[$a_parent_node_id])) {
                return $this->preloaded_children[$a_parent_node_id];
            }

            return [];
        }

        return $this->thread->getNestedSetPostChildren($a_parent_node_id, 1);
    }

    public function setCurrentPage(int $currentPage) : void
    {
        $this->currentPage = $currentPage;
    }

    protected function preloadChilds() : void
    {
        $this->preloaded_children = [];
        $this->node_id_to_parent_node_id_map = [];

        $children = $this->thread->getNestedSetPostChildren($this->root_node->getId());

        array_walk($children, function ($node, $key) {
            $this->node_id_to_parent_node_id_map[(int) $node['pos_pk']] = (int) $node['parent_pos'];

            if (!array_key_exists((int) $node['pos_pk'], $this->preloaded_children)) {
                $this->preloaded_children[(int) $node['pos_pk']] = [];
            }

            $this->preloaded_children[(int) $node['parent_pos']][$node['pos_pk']] = $node;
        });

        $this->preloaded = true;
    }

    public function getChildren($record, $environment = null) : array
    {
        return $this->getChildsOfNode((int) $record['pos_pk']);
    }

    public function getTreeLabel() : string
    {
        return $this->lng->txt("frm_posts");
    }

    public function getTreeComponent() : Tree
    {
        $rootNode = [
            [
                'pos_pk' => $this->root_node->getId(),
                'pos_subject' => $this->root_node->getSubject(),
                'pos_author_id' => $this->root_node->getPosAuthorId(),
                'pos_display_user_id' => $this->root_node->getDisplayUserId(),
                'pos_usr_alias' => $this->root_node->getUserAlias(),
                'pos_date' => $this->root_node->getCreateDate(),
                'import_name' => $this->root_node->getImportName(),
                'post_read' => $this->root_node->isPostRead()
            ]
        ];

        $tree = $this->ui->factory()->tree()
                         ->expandable($this->getTreeLabel(), $this)
                         ->withData($rootNode)
                         ->withHighlightOnNodeClick(false);

        return $tree;
    }

    protected function createNode(
        \ILIAS\UI\Component\Tree\Node\Factory $factory,
        $record
    ) : \ILIAS\UI\Component\Tree\Node\Node {
        $nodeIconPath = $this->getNodeIcon($record);

        $icon = null;
        if (is_string($nodeIconPath) && strlen($nodeIconPath) > 0) {
            $icon = $this->ui
                ->factory()
                ->symbol()
                ->icon()
                ->custom($nodeIconPath, $this->getNodeIconAlt($record));
        }

        if ((int) $record['pos_pk'] === $this->root_node->getId()) {
            $node = $factory->simple($this->getNodeContent($record), $icon);
        } else {
            $authorInfo = $this->getAuthorInformationByNode($record);
            $creationDate = ilDatePresentation::formatDate(new ilDateTime($record['pos_date'], IL_CAL_DATETIME));
            $bylineString = $authorInfo->getAuthorShortName() . ', ' . $creationDate;

            $node = $factory->bylined($this->getNodeContent($record), $bylineString, $icon);
        }

        return $node;
    }

    protected function getNodeStateToggleCmdClasses($record) : array
    {
        return [
            'ilRepositoryGUI',
            'ilObjForumGUI',
        ];
    }

    private function getAuthorInformationByNode(array $node) : ilForumAuthorInformation
    {
        if (isset($this->authorInformation[(int) $node['pos_pk']])) {
            return $this->authorInformation[(int) $node['pos_pk']];
        }

        return $this->authorInformation[(int) $node['pos_pk']] = new ilForumAuthorInformation(
            (int) ($node['pos_author_id'] ?? 0),
            (int) $node['pos_display_user_id'],
            (string) $node['pos_usr_alias'],
            (string) $node['import_name']
        );
    }

    public function getNodeId($a_node) : int
    {
        return (isset($a_node['pos_pk']) ? (int) $a_node['pos_pk'] : 0);
    }

    public function getNodeIcon($a_node) : string
    {
        if ((int) $this->root_node->getId() === (int) $a_node['pos_pk']) {
            return ilObject::_getIcon(0, 'tiny', 'frm');
        }

        return $this->getAuthorInformationByNode($a_node)->getProfilePicture();
    }

    public function getNodeHref($a_node) : string
    {
        if ((int) $this->root_node->getId() === (int) $a_node['pos_pk']) {
            return '';
        }

        $this->ctrl->setParameter($this->parent_obj, 'backurl', null);

        if (isset($a_node['counter']) && $a_node['counter'] > 0) {
            $page = (int) floor(($a_node['counter'] - 1) / $this->max_entries);
            $this->ctrl->setParameter($this->parent_obj, 'page', $page);
        }

        if (isset($a_node['post_read']) && $a_node['post_read']) {
            $this->ctrl->setParameter($this->parent_obj, 'pos_pk', null);
            $url = $this->ctrl->getLinkTarget($this->parent_obj, $this->parent_cmd, $a_node['pos_pk'], false, false);
        } else {
            $this->ctrl->setParameter($this->parent_obj, 'pos_pk', $a_node['pos_pk']);
            $url = $this->ctrl->getLinkTarget($this->parent_obj, 'markPostRead', $a_node['pos_pk'], false, false);
            $this->ctrl->setParameter($this->parent_obj, 'pos_pk', null);
        }

        $this->ctrl->setParameter($this->parent_obj, 'page', null);

        return $url;
    }

    public function getNodeContent($a_node) : string
    {
        return $a_node['pos_subject'];
    }
}
