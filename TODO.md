# Schedule Notification in Admin Push (Using notifications table only)

**Status**: Implementation Plan

## Information Gathered
- Admin push form: `resources/views/admin-views/notification/index.blade.php` (title, zone, tergat=customer/deliveryman, description, image)
- Controller: `NotificationController@add` → immediate FCM via trait `sendPushNotificationToTopic`
- Current schedule uses separate `ScheduledNotification` table (to be removed for push)
- Keep email scheduling as-is (uses ScheduledNotification)

## Plan
1. **Migration**: Add columns to `notifications` table:
   ```
   php artisan make:migration add_scheduling_to_notifications_table
   Schema::table('notifications', function (Blueprint $table) {
       $table->enum('status', ['draft', 'scheduled', 'sent', 'failed'])->default('draft');
       $table->timestamp('scheduled_at')->nullable();
       $table->timestamp('sent_at')->nullable();
   });
   ```

2. **New Job**: `app/Jobs/SendScheduledPushNotifications.php`
   ```
   notifications::where('status', 'scheduled')
   ->where('scheduled_at', '<=', now())
   ->chunk(10, fn($notifs) => $notifs->each(fn($n) => {
       try {
           NotificationTrait::sendPushNotificationToTopic($n->toArray(), $topic, 'general');
           $n->update(['status' => 'sent', 'sent_at' => now()]);
       } catch(e) {
           $n->update(['status' => 'failed']);
       }
   }));
   ```

3. **Kernel.php**:
   ```
   \$schedule->job(new SendScheduledPushNotifications)->everyMinute()
   ```

4. **NotificationController.php** (`add` method):
   ```
   \$isScheduled = \$request->boolean('is_scheduled');
   \$data['status'] = \$isScheduled ? 'scheduled' : 'sent';
   \$data['scheduled_at'] = \$isScheduled ? \$request->scheduled_at : null;
   \$notification = repo->add(\$data);
   if (!\$isScheduled) {
       // existing sendPushNotificationToTopic logic
   }
   Toastr::success(\$isScheduled ? 'Scheduled!' : 'Sent!');
   ```

5. **Blade JS** (`index.blade.php`):
   - Always POST to `notification.store`
   - Toggle → `formData.append('is_scheduled', true); formData.append('scheduled_at', datetime.val());`

## Next Steps (awaiting approval)
- Create migration
- Generate job class
- Edit controllers/kernel
- Test queue

**Approve plan before code changes.**

