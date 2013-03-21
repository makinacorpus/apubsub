<div id="notifications-page">
  <div class="list">
    <ul>
      <li>
        <div class="text">
          <?php echo $top; ?>
        </div>
      </li>
      <?php if (empty($list)): ?>
      <li class="empty">
        <div class="text">
          <?php echo t("You have no messages."); ?>
        </div>
      </li>
      <?php else: ?>
      <?php 
      $today = new DateTime('today');
      $delta = null;
      ?>
      <?php foreach ($list as $item): ?>
      <?php
        // Determine day.
        $itemDate = new DateTime('@' . $item['time']);
        $interval = $today->diff($itemDate);
        if (!$interval->invert) {
          // Today.
          $currentdelta = 0;
        } else {
          $currentdelta = (int)1 + $interval->d;
        }

        if ($currentdelta !== $delta) {
          $delta = $currentdelta;
          // We need to create a new title.
          switch ($delta) {
            case 0:
              $title = t("Today");
              break;
            case 1:
              $title = t("Yesterday");
              break;
            default:
              $title = format_plural($delta, "@count day ago", "@count days ago");
              break;
          }
        } else {
          $title = null;
        }
      ?>
      <?php if ($title): ?>
      <li>
        <div class="text">
          <h2><?php echo $title; ?></h2>
        </div>
      </li>
      <?php endif; ?>
      <li class="notification-<?php echo $item['type']; ?>">
        <div class="image">
          <?php echo render($item['image']); ?>
        </div>
        <div class="text">
          <div class="checkbox">
            <?php echo $item['checkbox']; ?>
          </div>
          <?php if ($item['unread']): ?>
          <span class="unread">
          <?php echo $item['text']; ?>
          </span>
          <?php else: ?>
          <?php echo $item['text']; ?>
          <?php endif; ?>
          <br/>
          <span class="time">
            <?php echo format_interval(time() - $item['time']); ?>
          </span>
        </div>
      </li>
      <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php echo $pager; ?>
  </div>
  <?php echo $form; ?>
</div>
