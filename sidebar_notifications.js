function updateGlobalUnreadCount() {
    $.getJSON('fetch_unread.php', function(data) {
        let totalUnread = 0;
        
        for (let uid in data) {
            totalUnread += parseInt(data[uid]);
        }

        const sidebarBadge = $('#sidebarUnreadBadge');
        if (sidebarBadge.length) {
            if (totalUnread > 0) {
                sidebarBadge.text(totalUnread).show();
            } else {
                sidebarBadge.text('0').hide();
            }
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Unread count fetch error: ", textStatus, errorThrown);
    });
}

$(document).ready(function() {
    updateGlobalUnreadCount();
    setInterval(updateGlobalUnreadCount, 2000);
});