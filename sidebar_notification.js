function updateGlobalUnreadCount() {
    $.getJSON('fetch_unread.php', function(data) {
        let totalUnread = 0;
        
        for (let uid in data) {
            totalUnread += parseInt(data[uid]);
        }

        const sidebarBadge = $('#sidebarUnreadBadge');
        if (sidebarBadge.length) {
            if (totalUnread > 0) {
                sidebarBadge.text(totalUnread).css('display', 'inline-block');
            } else {
                sidebarBadge.text('0').hide();
            }
        }
    });
}

$(document).ready(function() {
    updateGlobalUnreadCount();
    setInterval(updateGlobalUnreadCount, 2000);
});