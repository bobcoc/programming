M.mod_programming = {};
 
M.mod_programming.highlight_code = function(Y, name) {
    dp.sh.HighlightAll(name);
};

M.mod_programming.init_submit = function(Y) {
    Y.one('#submit').hide();

    Y.one('#submitagain').on('click', function() {
        Y.one('#submit').show();
        Y.one('#submitagainconfirm').hide();
    });
};

M.mod_programming.init_fetch_code = function(Y) {
    Y.all('a.submit').each(function(node) {
        node.on('click', function(evt) {
            evt.preventDefault();
            M.mod_programming.fetch_code(Y, node.getAttribute('submitid'));
            return false;
        });
    });
};

M.mod_programming.fetch_code = function(Y, submitid) {
    // Adjust element value of preview and print form
    var preview = Y.one('#print_preview_submit_id');
    if (preview != null) preview.value = submitid;
    var print = Y.one('#print_submit_id');
    if (print != null) print.value = submitid;

    Y.on('io:success', function(id, resp) {
        Y.one('#code').set('text', resp.responseText);
        Y.one('div.dp-highlighter').remove();
        dp.sh.HighlightAll('code');
    });
    var sUrl = 'history_fetch_code.php?submitid=' + submitid;
    Y.io(sUrl);
};

M.mod_programming.init_history = function(Y) {
    dp.sh.HighlightAll('code');

    var r = Y.all('.diff1');
    r.item(0).hide();
    r = Y.all('.diff2');
    r.item(r.size()-1).hide();

    var is_history_diff_form_submitable = function() {
        var f1 = false, f2 = false;
        Y.all('.diff1').each(function(node) {
            if (node.get('checked'))
                f1 = node;
        });
       
        Y.all('.diff2').each(function(node) {
            if (node.get('checked'))
                f2 = node;
        });
        return f1 && f2 && f1.get('value') != f2.get('value');
    };

    Y.one('#history-diff-form').on('submit', is_history_diff_form_submitable);

    Y.all('#history-diff-form input[type=radio]').each(function(node) {
        node.on('change', function() {
            var btn = Y.one('#history-diff-form input[type=submit]');
            if (is_history_diff_form_submitable()) {
                btn.removeAttribute('disabled');
            } else {
                btn.setAttribute('disabled', true);
            }
        });
    });

    Y.one('#history-diff-form input[type=submit]').setAttribute('disabled', true);
};

M.mod_programming.init_reports_detail = function(Y) {
    var switch_buttons_and_bar = function() {
        var show = false;
        Y.all('.selectsubmit').each(function(n1) {
            if (n1.get('checked')) show = true;
        });
        if (show) {
            Y.one('#submitbuttons').show();
            Y.one('.paging').hide();
        } else {
            Y.one('#submitbuttons').hide();
            Y.one('.paging').show();
        }
    };

    Y.all('.selectsubmit').each(function(node) {
        node.on('click', switch_buttons_and_bar);
    });
    Y.one('#rejudge').on('click', function() {
        Y.one('#submitaction').setAttribute('action', '../rejudge.php');
        Y.one('#submitaction').submit();
    });
    Y.one('#delete').on('click', function() {
        Y.one('#submitaction').setAttribute('action', '../deletesubmit.php');
        Y.one('#submitaction').submit();
    });

    switch_buttons_and_bar();

    Y.all("#mform1 select").each(function(node) {
        node.on('change', function() {
            Y.one("#mform1").submit();
        });
    });

};

M.mod_programming.draw_summary_percent_chart = function(Y, data) {
    
    var myDataValues = eval(data);
    var pieGraph = new Y.Chart({
            render:"#summary-percent-chart",
            categoryKey:"result",
            seriesKeys:["count"],
            dataProvider:myDataValues,
            type:"pie",
            seriesCollection:[
                {
                    categoryKey:"result",
                    valueKey:"count"
                }
            ]
    });
};

M.mod_programming.draw_summary_group_count_chart = function(Y, data) {
    var myDataValues = eval(data);
    var mychart = new Y.Chart({
            dataProvider:myDataValues,
            render:"#summary-group-count-chart",
            type:"column"
    });
};

M.mod_programming.draw_judgeresult_chart = function(Y, data) {
    
    var myDataValues = eval(data);
    var pieGraph = new Y.Chart({
            render:"#judgeresult-chart",
            categoryKey:"result",
            seriesKeys:["count"],
            dataProvider:myDataValues,
            legend: {
                position: "right",
                styles: {
                    hAlign: "center",
                    hSpacing: 4
                }
            },
            type:"pie",
            seriesCollection:[
                {
                    categoryKey:"result",
                    valueKey:"count"
                }
            ]
    });
};
