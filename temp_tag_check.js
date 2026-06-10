const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'resources/js/Pages/CU03GestionarPostulantes.vue');
const text = fs.readFileSync(file, 'utf8');
const regex = /<(\/?)(([A-Za-z][A-Za-z0-9\-]*))(?:[^>]*)>/g;
const selfClosing = new Set(['input','br','img','hr','meta','link','source','area','col','embed','param','track','wbr']);
const stack = [];
text.split(/\r?\n/).forEach((line, i) => {
  let m;
  while ((m = regex.exec(line)) !== null) {
    const [match, slash, tag] = m;
    if (selfClosing.has(tag.toLowerCase()) || /\/$/.test(m[0])) continue;
    if (slash) {
      if (stack.length && stack[stack.length-1] === tag) {
        stack.pop();
      } else {
        console.log('unexpected close', tag, 'line', i+1, 'stack', JSON.stringify(stack));
      }
    } else {
      stack.push(tag);
    }
  }
});
if (stack.length) {
  console.log('remaining stack:', JSON.stringify(stack.slice(-20)));
}
