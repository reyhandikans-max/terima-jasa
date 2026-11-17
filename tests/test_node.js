
let text = "**bold** and *italic* and \n• item 1\n• item 2";
let html1 = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
let html2 = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
console.log("Double backslash:", html1);
console.log("Single backslash:", html2);
