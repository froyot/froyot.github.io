// 所有模块都通过 define 来定义
define(function(require, exports, module) {
  // 通过 require 引入依赖


  var styleClient = $('#style-tag');
  var str0 = '';
  var str1 = '';
  var str2 = '';

  //导入css文件
  function importStyleFile(index)
  {
    $.get('./js/css/main'+index+'.css',function(data){
      handleData( index, data, $('#work-text'+index));
    });

  }

  function handleData( index,str, element )
  {

    writeTo(element, str,function(){
      if(index < 3)
        importStyleFile(++index);
    });

  }

  //向控件写字符串
  function writeTo(element, str, callFun)
  {
    styleClient.typed({
            strings: [str],
            showCursor: false,
            contentType: 'text',
    });
    var handleStr = handleText(str);
    element.typed({
            strings: [handleStr],
            showCursor: false,
            contentType: 'html',
            callback:function(){
                if( undefined != callFun )
                  callFun();
            }
    });
  }

  //开始
  function start() {
      importStyleFile(0);
  }

  //检测窗口是否在最上层
  function checkWindow()
  {

  }
  function sleep(numberMillis) {
  　　var now = new Date();
  　　var exitTime = now.getTime() + numberMillis;
  　　while (true) {
  　　now = new Date();
  　　if (now.getTime() > exitTime)
  　　  return;
  　　}
　　}

function handleText(str)
{
  var fullText = '';
  for(var i = 0; i < str.length ; i++ )
  {
    fullText = handleChar(fullText, str[i]);
  }
  return fullText;
}
var openComment = false;
var commentRegex = /(\/\*(?:[^](?!\/\*))*\*)$/;
var keyRegex = /([a-zA-Z- ^\n]*)$/;
var valueRegex = /([^:]*)$/;
var selectorRegex = /(.*)$/;
var pxRegex = /\dp/;
var pxRegex2 = /p$/;

 function handleChar(fullText, char) {
  if (openComment && char !== '/') {
    // Short-circuit during a comment so we don't highlight inside it.
    fullText += char;
  } else if (char === '/' && openComment === false) {
    openComment = true;
    fullText += char;
  } else if (char === '/' && fullText.slice(-1) === '*' && openComment === true) {
    openComment = false;
    // Unfortunately we can't just open a span and close it, because the browser will helpfully
    // 'fix' it for us, and we'll end up with a single-character span and an empty closing tag.
    fullText = fullText.replace(commentRegex, '<span class="comment">$1/</span>');
  } else if (char === ':') {
    fullText = fullText.replace(keyRegex, '<span class="key">$1</span>:');
  } else if (char === ';') {
    fullText = fullText.replace(valueRegex, '<span class="value">$1</span>;');
  } else if (char === '{') {
    fullText = fullText.replace(selectorRegex, '<span class="selector">$1</span>{');
  } else if (char === 'x' && pxRegex.test(fullText.slice(-2))) {
    fullText = fullText.replace(pxRegex2, '<span class="value px">px</span>');
  } else {
    fullText += char;
  }
  return fullText;
}


  exports.start = function(){
    start();
  }

});
