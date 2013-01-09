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
      <?php foreach ($list as $item): ?>
      <li>
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
