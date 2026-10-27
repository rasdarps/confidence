$(function(){
    $(document).on('click','#delete',function(e){
        e.preventDefault();
        var link = $(this).attr("href");

        if (window.Notiflix && Notiflix.Confirm) {
            Notiflix.Confirm.show(
                'Confirm Delete',
                'Delete this data?',
                'Yes, delete it',
                'Cancel',
                function okCb() {
                    window.location.href = link;
                },
                function cancelCb() {},
                {
                    width: '320px',
                    borderRadius: '8px',
                    titleColor: '#d33',
                    okButtonBackground: '#d33',
                    cancelButtonBackground: '#6c757d'
                }
            );
        } else {
            if (confirm('Delete this data?')) {
                window.location.href = link;
            }
        }


    });

  });
