# PowerShell / Windows Terminal Cheat Sheat
Listed below are some Windows Terminal commands that come in handy.

## Grep Equivalent Command
The VS Code search functions and filters are nearly always up to the task of finding code, or a string, in a filesystem. However, it's nice to have a cli command as well. The block below searches in a directory, and it's children, for files of a certain file-type (e.g. ".json" in the code below), containing a specific word (e.g. "SearchWord" in the code below). Matches are then written into a file for easy review:

```
// Grep equivalent - use in PowerShell
Clear-Host
Get-ChildItem E:\Path\To\Directory\ -Recurse -Filter '*.json' -File |
foreach {
  # if the content in the file matches 'SearchWord'
  if((Get-Content $_.FullName) -match 'SearchWord'){
    # if it matches, write the path out
    $_.FullName | out-file -append E:\Path\To\Result\File\grep_results.txt
    $_.FullName
  }
}
```

## Symlink
Create a symbolic link:

```
New-Item -ItemType SymbolicLink -Path "\Link\To\Fuax\Endpoint" -Target "\Link\To\Actual\Files"
```

## Update Date Modified
I had a copier that only ordered files on my thumbdrives based on modifed date, not titles :/   This one-liner let me order my files :)

```
(Get-Item "D:\Path\To\File.pdf").LastWriteTime = '06/20/2023 01:02:03'
```
