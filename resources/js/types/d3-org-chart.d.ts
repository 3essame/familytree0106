declare module 'd3-org-chart' {
  export class OrgChart {
    constructor();
    
    // Common methods used in your component
    container(element: HTMLElement): this;
    data(data: any[]): this;
    nodeWidth(width: number): this;
    nodeHeight(height: number): this;
    childrenMargin(margin: number): this;
    compactMarginBetween(margin: number): this;
    compactMarginPair(margin: number): this;
    siblingsMargin(margin: number): this;
    nodeContent(callback: (d: any) => string): this;
    onNodeClick(callback: (node: any) => void): this;
    render(): this;
    zoomOut(): this;
    fit(): this;
  }
}