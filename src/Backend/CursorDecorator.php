<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\CursorInterface;

/**
 * Usefull to decorate an existing cursor and change a single behavior
 *
 * @see \MakinaCorpus\APubSub\Messenging\ThreadCursor for a great example
 */
class CursorDecorator implements \IteratorAggregate, CursorInterface
{
    /**
     * @var CursorInterface
     */
    private $innerCursor;

    /**
     * Default constructor
     *
     * @param CursorInterface $cursor
     */
    public function __construct(CursorInterface $cursor)
    {
        $this->innerCursor = $cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSorts()
    {
        return $this->innerCursor->getAvailableSorts();
    }

    /**
     * {@inheritdoc}
     */
    public function addSort($sort, $order = CursorInterface::SORT_ASC)
    {
        $this->innerCursor->addSort($sort, $order = CursorInterface::SORT_ASC);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit($limit)
    {
        $this->innerCursor->setLimit($limit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset($offset)
    {
        $this->innerCursor->setOffset($offset);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRange($limit, $offset)
    {
        $this->innerCursor->setRange($limit, $offset);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        return $this->innerCursor->getTotalCount();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->innerCursor->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $values)
    {
        $this->innerCursor->update($values);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->innerCursor->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->innerCursor;
    }
}
